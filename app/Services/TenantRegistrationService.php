<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Mail\WelcomeTenantMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        // 1. Validate uniqueness
        $this->validateUniqueness($data['subdomain'], $data['email']);

        DB::beginTransaction();

        try {
            // 2. Create Tenant in central DB
            $tenant = Tenant::create([
                'id' => $data['subdomain'],
            ]);

            $tenant->update([
                'name'     => $data['business_name'],
                'email'    => $data['email'],
                'country'  => $data['country'] ?? null,
                'language' => $data['language'] ?? 'en',
                'active'   => true,
            ]);

            // 3. Create domain
            $domain = $tenant->domains()->create([
                'domain' => $this->buildSubdomain($data['subdomain']),
            ]);

            // 4. Initialize tenant database (runs tenant migrations)
            $tenant->run(function () {
                // DB is auto-created + migrations run via tenancy events
            });

            // Force run migrations for this tenant
            \Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
            ]);

            // 5. Create Admin User inside tenant DB
            $tenant->run(function () use ($data) {
                $adminRole = \App\Models\Role::firstOrCreate(
                    ['name' => 'Admin Tenant'],
                    ['display_name' => 'Admin', 'description' => 'Tenant Administrator']
                );

                \App\Models\User::create([
                    'role_id'  => $adminRole->id,
                    'name'     => $data['business_name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                // Create default settings
                \App\Models\Setting::firstOrCreate(
                    ['id' => 1],
                    [
                        'business_name'      => $data['business_name'],
                        'language'           => $data['language'] ?? 'en',
                        'timezone'           => 'UTC',
                        'booking_enabled'    => true,
                        'queue_enabled'      => true,
                        'available_languages' => json_encode(['en', 'ar']),
                    ]
                );
            });

            // 6. Create Trial Subscription using selected or cheapest plan
            $trialPlan = isset($data['plan_id'])
                ? SubscriptionPlan::where('is_active', true)->find($data['plan_id'])
                : null;

            // Fallback to cheapest active plan
            if (!$trialPlan) {
                $trialPlan = SubscriptionPlan::where('is_active', true)
                    ->orderBy('price', 'asc')
                    ->first();
            }

            $trialDays  = $trialPlan?->trial_days ?? 14;
            $graceDays  = 3;

            $subscription = TenantSubscription::create([
                'tenant_id'            => $tenant->id,
                'subscription_plan_id' => $trialPlan?->id ?? 1,
                'status'               => 'trial',
                'trial_ends_at'        => now()->addDays($trialDays),
                'grace_ends_at'        => now()->addDays($trialDays + $graceDays),
                'starts_at'            => now(),
                'ends_at'              => null,
                'amount_paid'          => 0,
                'payment_method'       => null,
                'notes'                => 'Auto-created trial subscription',
            ]);

            DB::commit();

            // 7. Send Welcome Email
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

            $redirectUrl = 'https://' . $this->buildSubdomain($data['subdomain']) . '/admin/dashboard';

            return [
                'tenant'       => $tenant,
                'subdomain'    => $data['subdomain'],
                'redirect_url' => $redirectUrl,
                'subscription' => $subscription,
                'trial_days'   => $trialDays,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tenant registration failed: ' . $e->getMessage(), [
                'subdomain' => $data['subdomain'],
                'email'     => $data['email'],
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check subdomain availability (AJAX endpoint helper)
     */
    public function checkSubdomainAvailability(string $subdomain): array
    {
        $subdomain = strtolower(trim($subdomain));

        $reserved = ['www', 'admin', 'api', 'mail', 'cdn', 'app', 'dashboard',
                     'support', 'help', 'billing', 'status', 'dev', 'staging',
                     'test', 'demo', 'beta', 'secure', 'login', 'signup'];

        if (in_array($subdomain, $reserved)) {
            return ['available' => false, 'message' => 'This subdomain is reserved.'];
        }

        if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]$/', $subdomain)) {
            return ['available' => false, 'message' => 'Subdomain must be 3-32 lowercase alphanumeric characters or hyphens.'];
        }

        $exists = \Stancl\Tenancy\Database\Models\Domain::where(
            'domain', $this->buildSubdomain($subdomain)
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
}
