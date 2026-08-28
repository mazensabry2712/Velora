<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Subscription\SubscriptionLifecycle;
use App\Mail\WelcomeTenantMail;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\Models\Role;

final class TenantRegistrationService
{
    public function register(array $data): array
    {
        $this->validateUniqueness($data['subdomain'], $data['email']);

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

        $tenant = null;
        $subscription = null;
        $trialStartsAt = now();
        $trialEndsAt = $trialStartsAt->copy()->addDays(SubscriptionLifecycle::TRIAL_DAYS);
        $lockedAt = SubscriptionLifecycle::lockedAt($trialEndsAt);
        $deletionAt = SubscriptionLifecycle::deletionAt($trialEndsAt);

        try {
            try {
                $tenant = Tenant::create(['id' => $data['subdomain']]);
            } catch (QueryException $e) {
                if ($this->isDuplicateKeyException($e)) {
                    throw ValidationException::withMessages([
                        'subdomain' => 'This subdomain is already taken.',
                    ]);
                }

                throw $e;
            }

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

            // TenantCreated already provisions the tenant database, runs the
            // tenant migrations and seeds the tenant database synchronously.
            // Do not run tenants:migrate a second time here.

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
                        'available_languages' => json_encode(config('localizer.supported_locales', ['en', 'ar'])),
                    ]
                );

                auth()->login($user);
                session()->regenerate();
            });

            $subscription = TenantSubscription::create([
                'tenant_id'            => $tenant->id,
                'subscription_plan_id' => $trialPlan->id,
                'status'               => 'trial',
                'trial_ends_at'        => $trialEndsAt,
                'read_only_ends_at'    => $lockedAt,
                'locked_at'            => $lockedAt,
                'deletion_at'          => $deletionAt,
                'grace_ends_at'        => null,
                'starts_at'            => $trialStartsAt,
                'ends_at'              => null,
                'amount_paid'          => 0,
                'payment_method'       => $this->resolveGatewayForCountry($data['country'] ?? 'US'),
                'notes'                => 'Auto-created 7-day trial. Read-only for 14 days, locked for 6 days, then permanently deleted.',
            ]);
        } catch (ValidationException $e) {
            $this->cleanupFailedTenant($tenant);
            throw $e;
        } catch (\Throwable $e) {
            $this->cleanupFailedTenant($tenant);

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
                SubscriptionLifecycle::TRIAL_DAYS
            ));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed for tenant ' . $tenant->id . ': ' . $e->getMessage());
        }

        $scheme = str_starts_with(config('app.url', 'http://velora.com'), 'https') ? 'https' : 'http';
        $redirectUrl = $scheme . '://' . $this->buildSubdomain($data['subdomain']) . '/admin/dashboard';

        return [
            'tenant'       => $tenant,
            'subdomain'    => $data['subdomain'],
            'redirect_url' => $redirectUrl,
            'subscription' => $subscription,
            'trial_days'   => SubscriptionLifecycle::TRIAL_DAYS,
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

        if (in_array($subdomain, $reserved, true)) {
            return ['available' => false, 'message' => 'This subdomain is reserved.'];
        }

        if (! preg_match('/^[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]$/', $subdomain)) {
            return ['available' => false, 'message' => 'Subdomain must be 3-32 lowercase alphanumeric characters or hyphens.'];
        }

        $tenantExists = Tenant::withTrashed()
            ->whereKey($subdomain)
            ->exists();

        if ($tenantExists) {
            return ['available' => false, 'message' => 'This subdomain is already taken.'];
        }

        $domainExists = \Stancl\Tenancy\Database\Models\Domain::where(
            'domain',
            $this->buildSubdomain($subdomain)
        )->exists();

        if ($domainExists) {
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

        $tenants = DB::table('tenants');

        if (DB::connection()->getDriverName() === 'sqlite') {
            $emailExists = $tenants
                ->whereRaw("LOWER(json_extract(data, '$.email')) = LOWER(?)", [$email])
                ->exists();
        } else {
            $emailExists = $tenants
                ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, '$.email'))) = LOWER(?)", [$email])
                ->exists();
        }

        if ($emailExists) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }
    }

    private function cleanupFailedTenant(?Tenant $tenant): void
    {
        if (! $tenant) {
            return;
        }

        try {
            $tenant->forceDelete();
        } catch (\Throwable $cleanupException) {
            Log::critical('Failed to clean up partially-created tenant.', [
                'tenant_id' => $tenant->getKey(),
                'error' => $cleanupException->getMessage(),
            ]);
        }
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23000' && (int) $driverCode === 1062;
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

        if (in_array($code, ['SA', 'AE', 'KW', 'BH', 'OM', 'QA'], true)) {
            return 'moyasar';
        }

        return 'stripe';
    }
}
