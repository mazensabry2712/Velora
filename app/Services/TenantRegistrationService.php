<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Tenant\Contracts\TenantRegistrar;
use App\Events\TenantProvisioningRequested;
use App\Mail\VerifyTenantEmailMail;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class TenantRegistrationService implements TenantRegistrar
{
    private const EMAIL_VERIFICATION_TTL_HOURS = 24;

    public function register(array $data): array
    {
        $data['subdomain'] = strtolower(trim((string) $data['subdomain']));
        $data['email'] = strtolower(trim((string) $data['email']));
        $data['business_name'] = trim((string) $data['business_name']);

        $this->validateUniqueness($data['subdomain'], $data['email']);

        $supportedLocales = config('localizer.supported_locales', ['ar', 'en']);
        $publicDefaultLocale = $this->publicDefaultLocale($supportedLocales);
        $requestedLocale = $data['language'] ?? null;
        $tenantLocale = is_string($requestedLocale) && in_array($requestedLocale, $supportedLocales, true)
            ? $requestedLocale
            : $publicDefaultLocale;

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

        $subdomain = $data['subdomain'];
        $provisioningToken = $subdomain.'.'.Str::random(48);
        $verificationToken = $subdomain.'.'.Str::random(64);
        $verificationUrl = url('/email/verify/'.$verificationToken);
        $verificationExpiresAt = now()->addHours(self::EMAIL_VERIFICATION_TTL_HOURS);

        try {
            $tenant = DB::transaction(function () use (
                $data,
                $tenantLocale,
                $trialPlan,
                $subdomain,
                $provisioningToken,
                $verificationToken,
                $verificationUrl,
                $verificationExpiresAt
            ): Tenant {
                $tenant = Tenant::create([
                    'id' => $subdomain,
                    'name' => $data['business_name'],
                    'email' => $data['email'],
                    'country' => $data['country'] ?? null,
                    'language' => $tenantLocale,
                    'active' => true,
                    'gateway' => $this->resolveGatewayForCountry($data['country'] ?? 'US'),
                    'business_type' => $data['business_type'] ?? null,
                    'subscription_plan_id' => $trialPlan->id,
                    'provisioning_status' => 'queued',
                    'provisioning_message' => 'Your workspace is being prepared.',
                    'provisioning_token_hash' => hash('sha256', $provisioningToken),
                    'provisioning_token_encrypted' => Crypt::encryptString($provisioningToken),
                    'provisioning_email' => $data['email'],
                    'provisioning_password' => Crypt::encryptString($data['password']),
                    'email_verification_token_hash' => hash('sha256', $verificationToken),
                    'email_verification_token_encrypted' => Crypt::encryptString($verificationToken),
                    'email_verification_expires_at' => $verificationExpiresAt,
                    'email_verification_url' => $verificationUrl,
                    'email_verified_at' => null,
                    'email_verification_token_used_at' => null,
                ]);

                $tenant->domains()->create([
                    'domain' => $this->buildSubdomain($subdomain),
                ]);

                return $tenant;
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                throw ValidationException::withMessages([
                    'subdomain' => 'This subdomain is already taken.',
                ]);
            }

            throw $e;
        }

        Event::dispatch(new TenantProvisioningRequested($tenant));

        Mail::to($data['email'])->queue(new VerifyTenantEmailMail(
            $data['business_name'],
            $tenant->id,
            $this->buildSubdomain($subdomain),
            $verificationUrl,
            self::EMAIL_VERIFICATION_TTL_HOURS,
        ));

        $base = rtrim(config('app.base_domain', 'velora.test'), '.');

        return [
            'tenant' => $tenant,
            'subdomain' => $subdomain,
            'provisioning_token' => $provisioningToken,
            'provisioning_url' => url('/signup/provisioning/'.$provisioningToken),
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

        $loginUrl = $this->resolveTenantLoginUrl($subdomain);

        if ($loginUrl !== null) {
            return [
                'available' => false,
                'message' => 'This subdomain is already taken.',
                'login_url' => $loginUrl,
            ];
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

    public function resolveTenantLoginUrl(string $subdomain): ?string
    {
        $subdomain = strtolower(trim($subdomain));

        if (! preg_match('/^[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]$/', $subdomain)) {
            return null;
        }

        $expectedDomain = $this->buildSubdomain($subdomain);
        $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $expectedDomain)->first();

        if ($domain) {
            $tenant = $domain->tenant;
            if (! $tenant || ! $tenant->active || (method_exists($tenant, 'trashed') && $tenant->trashed())) {
                return null;
            }

            $resolvedDomain = $domain->domain;
        } else {
            $tenant = Tenant::query()->whereKey($subdomain)->first();
            if (! $tenant || ! $tenant->active) {
                return null;
            }

            $resolvedDomain = $tenant->domain !== 'unknown'
                ? $tenant->domain
                : $expectedDomain;
        }

        $scheme = request()->isSecure() ? 'https' : 'http';

        return $scheme.'://'.$resolvedDomain.'/login';
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
            ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, '$.email'))) = LOWER(?)", [$email])
            ->exists();

        if ($emailExists) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }
    }

    private function publicDefaultLocale(array $supportedLocales): string
    {
        $fallback = 'ar';

        try {
            $configured = SystemSetting::get('public_default_locale', $fallback);
            if (is_string($configured) && in_array($configured, $supportedLocales, true)) {
                return $configured;
            }
        } catch (\Throwable) {
        }

        return in_array($fallback, $supportedLocales, true)
            ? $fallback
            : ($supportedLocales[0] ?? 'en');
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
