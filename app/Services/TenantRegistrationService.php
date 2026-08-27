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
    public function register(array $data): array
    {
        $this->validateUniqueness($data['subdomain'], $data['email']);

        $promoCode = null;
        if (! empty($data['promo_code'])) {
            $promoCode = PromoCode::where('code', strtoupper(trim($data['promo_code'])))->first();

            if (! $promoCode || ! $promoCode->isValid()) {
                throw ValidationException::withMessages([
                    'promo_code' => ['This promo code is invalid or has expired.'],
                ]);
            }
        }

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

            $tenant->domains()->create([
                'domain' => $this->buildSubdomain($data['subdomain']),
            ]);

            // The first admin is created in the tenant DB and immediately
            // authenticated so signup can continue directly into onboarding.
            $tenant->run(function () use ($data, &$subscription, &$tenant) {
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

                // Persist the authenticated user in the shared session. The
                // tenant middleware will be active again when the browser
                // follows the cross-subdomain redirect to /admin/dashboard.
                auth()->login($user);
                session()->regenerate();
            });

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

            if ($promoCode) {
                $promoCode->incrementUsage();
            }
        } catch (\Exception $e) {
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

    public function checkSubdomainAvailability(string $subdomain): array
    {
        $subdomain = strtolower(trim($subdomain));

        $reserved = [
            'www', 'admin', 'api', 'mail', 'cdn', 'app', 'dashboard',
            'support', 'help', 'billing', 'status', 'dev', 'staging', 'test',
            'demo', 'beta', 'secure', 'login', 'signup'
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
        if (! $subdomainCheck['available']) {
            throw ValidationException::withMessages([
                'subdomain' => $subdomainCheck['message'],
            ]);
        }

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
