<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
            throw new RuntimeException(
                'No active subscription plan is configured. Please seed the subscription_plans table.'
            );
        }

        $subdomain = strtolower(trim($data['subdomain']));
        $token = $subdomain.'.'.Str::random(48);

        try {
            $tenant = Tenant::create([
                'id' => $subdomain,
                'name' => $data['business_name'],
                'email' => $data['email'],
                'country' => $data['country'] ?? null,
                'language' => $data['language'] ?? 'en',
                'active' => true,
                'gateway' => $this->resolveGatewayForCountry($data['country'] ?? 'US'),
                'business_type' => $data['business_type'] ?? null,
                'subscription_plan_id' => $trialPlan->id,
                'provisioning_status' => 'queued',
                'provisioning_message' => 'Your workspace is being prepared.',
                'provisioning_token_hash' => hash('sha256', $token),
                'provisioning_email' => $data['email'],
                'provisioning_password' => Crypt::encryptString($data['password']),
            ]);

            $tenant->domains()->create([
                'domain' => $this->buildSubdomain($subdomain),
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                throw ValidationException::withMessages([
                    'subdomain' => 'This subdomain is already taken.',
                ]);
            }

            throw $e;
        }

        $base = rtrim(config('app.base_domain', 'velora.test'), '.');

        return [
            'tenant' => $tenant,
            'subdomain' => $subdomain,
            'provisioning_token' => $token,
            'provisioning_url' => url('/signup/provisioning/'.$token),
            'redirect_url' => 'http://'.$subdomain.'.'.$base.'/admin/onboarding',
            'trial_days' => 7,
        ];
    }

    public function checkSubdomainAvailability(string $subdomain): array
    {
        $subdomain = strtolower(trim($subdomain));

        $reserved = [
            'www', 'admin', 'api', 'mail', 'cdn', 'app', 'dashboard',
            'support', 'help', 'billing', 'status', 'dev', 'staging', 'test',
            'demo', 'beta', 'secure', 'login', 'signup',
        ];

        if (in_array($subdomain, $reserved, true)) {
            return ['available' => false, 'message' => 'This subdomain is reserved.'];
        }

        if (! preg_match('/^[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]$/', $subdomain)) {
            return ['available' => false, 'message' => 'Subdomain must be 3-32 lowercase alphanumeric characters or hyphens.'];
        }

        if (Tenant::withTrashed()->whereKey($subdomain)->exists()) {
            return ['available' => false, 'message' => 'This subdomain is already taken.'];
        }

        if (\Stancl\Tenancy\Database\Models\Domain::where(
            'domain',
            $this->buildSubdomain($subdomain)
        )->exists()) {
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
        $emailExists = DB::connection()->getDriverName() === 'sqlite'
            ? $tenants->whereRaw("LOWER(json_extract(data, '$.email')) = LOWER(?)", [$email])->exists()
            : $tenants->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, '$.email'))) = LOWER(?)", [$email])->exists();

        if ($emailExists) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
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
        $base = config('app.base_domain', 'velora.test');
        return $slug.'.'.$base;
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
