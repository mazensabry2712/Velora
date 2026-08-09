<?php

namespace App\Services;

use App\Mail\WelcomeTenantMail;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\Models\Role;

class TenantRegistrationService
{
    /**
     * Register a new tenant with trial subscription.
     *
     * @param array $data {
     *   business_name, subdomain, email, password, country?, language?
     * }
     * @return array { tenant, subdomain, redirect_url }
     */
    public function register(array $data): array
    {
        // ── Step 1: Validate uniqueness first — prevents duplicate tenants
        $this->validateUniqueness($data['subdomain'], $data['email']);

        // ── Promo code validation (before any DB writes) ──────────────────
        $promoCode = null;
        if (! empty($data['promo_code'])) {
            $promoCode = PromoCode::where('code', strtoupper(trim($data['promo_code'])))->first();

            if (! $promoCode || ! $promoCode->isValid()) {
                throw ValidationException::withMessages([
                    'promo_code' => ['This promo code is invalid or has expired.'],
                ]);
            }
        }

        // ── Resolve the trial plan up front — fail fast with a clear error
        //    instead of hitting a foreign key violation later on.
        $trialPlan = isset($data['plan_id'])
            ? SubscriptionPlan::where('is_active', true)->find($data['plan_id'])
            : null;

        if (! $trialPlan) {
            $trialPlan = SubscriptionPlan::where('is_active', true)
                ->orderBy('price', 'asc')
                ->first();
        }

        if (! $trialPlan) {
            Log::error('Tenant registration failed: no active subscription plan found.');
            throw new RuntimeException(
                'No active subscription plan is configured. Please seed the subscription_plans table.'
            );
        }

        $tenant       = null;
        $trialDays    = $trialPlan->trial_days ?? 14;
        $subscription = null;

        try {
            // ── Step 2: Create Tenant (fires TenantCreated → CreateDatabase + MigrateDatabase synchronously) ──
            $tenant = Tenant::create(['id' => $data['subdomain']]);

            $tenant->update([
                'name'          => $data['business_name'],
                'email'         => $data['email'],
                'country'       => $data['country'] ?? null,
                'language'      => $data['language'] ?? 'en',
                'active'        => true,
                'gateway'       => $this->resolveGatewayForCountry($data['country'] ?? 'US'),
                'business_type' => $data['business_type'] ?? null,
            ]);

            // ── Step 3: Create domain (fires DomainCreated → LinkTenantDomain job) ──
            $tenant->domains()->create([
                'domain' => $this->buildSubdomain($data['subdomain']),
            ]);

            // ── Step 4: Create Admin User + settings inside tenant DB ──
            $tenant->run(function () use ($data) {
                $adminRole = Role::firstOrCreate(
                    ['name' => 'Admin Tenant', 'guard_name' => 'web']
                );

                $user = User::create([
                    'name'     => $data['business_name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                $user->assignRole($adminRole);

                Setting::firstOrCreate(
                    ['id' => 1],
                    [
                        'business_name'       => $data['business_name'],
                        'language'            => $data['language'] ?? 'en',
                        'timezone'            => 'UTC',
                        'booking_enabled'     => true,
                        'queue_enabled'       => true,
                        'available_languages' => json_encode(['en', 'ar']),
                    ]
                );
            });

            // ── Step 5: Back on central DB — create trial subscription ──
            $graceDays = 3;

            $subscription = TenantSubscription::create([
                'tenant_id'            => $tenant->id,
                'subscription_plan_id' => $trialPlan->id,
                'status'               => 'trial',
                'trial_ends_at'        => now()->addDays($trialDays),
                'grace_ends_at'        => now()->addDays($trialDays + $graceDays),
                'starts_at'            => now(),
                'ends_at'              => null,
                'amount_paid'          => 0,
                'payment_method'       => $this->resolveGatewayForCountry($data['country'] ?? 'US'),
                'notes'                => 'Auto-created trial subscription'
                    . ($promoCode ? ' | promo: ' . $promoCode->code : ''),
            ]);

            // Increment promo code usage now that the subscription is committed
            if ($promoCode) {
                $promoCode->incrementUsage();
            }
        } catch (\Exception $e) {
            // Cleanup: delete tenant (TenantDeleted event will drop the tenant DB automatically)
            if ($tenant) {
                try {
                    $tenant->delete();
                } catch (\Exception $ignored) {
                }
            }

            Log::error('Tenant registration failed: ' . $e->getMessage(), [
                'subdomain' => $data['subdomain'],
                'email'     => $data['email'],
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // ── Step 6: Welcome email (non-blocking) ─────────────────────────────
        try {
            Mail::to($data['email'])->send(new WelcomeTenantMail(
                $data['business_name'],
                $data['subdomain'],
                $this->buildSubdomain($data['subdomain']),
                $trialDays
            ));
        } catch (\Exception $e) {
            Log::warning('Welcome email failed for tenant ' . $tenant->id . ': ' . $e->getMessage());
        }

        $scheme      = str_starts_with(config('app.url', 'http://velora.com'), 'https') ? 'https' : 'http';
        $redirectUrl = $scheme . '://' . $this->buildSubdomain($data['subdomain']) . '/admin/dashboard';

        return [
            'tenant'       => $tenant,
            'subdomain'    => $data['subdomain'],
            'redirect_url' => $redirectUrl,
            'subscription' => $subscription,
            'trial_days'   => $trialDays,
        ];
    }

    /**
     * Check subdomain availability (AJAX endpoint helper)
     */
    public function checkSubdomainAvailability(string $subdomain): array
    {
        $subdomain = strtolower(trim($subdomain));

        $reserved = [
            'www',
            'admin',
            'api',
            'mail',
            'cdn',
            'app',
            'dashboard',
            'support',
            'help',
            'billing',
            'status',
            'dev',
            'staging',
            'test',
            'demo',
            'beta',
            'secure',
            'login',
            'signup'
        ];

        if (in_array($subdomain, $reserved)) {
            return ['available' => false, 'message' => 'This subdomain is reserved.'];
        }

        if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]$/', $subdomain)) {
            return ['available' => false, 'message' => 'Subdomain must be 3-32 lowercase alphanumeric characters or hyphens.'];
        }

        $exists = \Stancl\Tenancy\Database\Models\Domain::where(
            'domain',
            $this->buildSubdomain($subdomain)
        )->exists();

        if ($exists) {
            return ['available' => false, 'message' => 'This subdomain is already taken.'];
        }

        return ['available' => true, 'message' => 'Great! This subdomain is available.'];
    }

    private function validateUniqueness(string $subdomain, string $email): void
    {
        $subdomainCheck = $this->checkSubdomainAvailability($subdomain);
        if (!$subdomainCheck['available']) {
            throw ValidationException::withMessages([
                'subdomain' => $subdomainCheck['message'],
            ]);
        }

        // Check email uniqueness across all tenant DBs (central meta check)
        $emailExists = DB::table('tenants')
            ->whereRaw("JSON_EXTRACT(data, '$.email') = ?", [$email])
            ->exists();

        if ($emailExists) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }
    }

    private function buildSubdomain(string $slug): string
    {
        $base = config('app.base_domain', 'velora.com');
        return $slug . '.' . $base;
    }

    /**
     * Map a country code to the appropriate payment gateway.
     * EG → Paymob | SA/AE/KW/BH/OM/QA → Moyasar | default → Stripe
     */
    private function resolveGatewayForCountry(string $countryCode): string
    {
        $code = strtoupper(trim($countryCode));

        if ($code === 'EG') {
            return 'paymob';
        }

        if (in_array($code, ['SA', 'AE', 'KW', 'BH', 'OM', 'QA'])) {
            return 'moyasar';
        }

        return 'stripe';
    }
}
