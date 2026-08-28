<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyTenantEmailMail;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class TenantProvisioningController extends Controller
{
    private const TOKEN_TTL_MINUTES = 30;
    private const EMAIL_VERIFICATION_TTL_HOURS = 24;

    public function show(Request $request, string $token)
    {
        $tenant = $this->resolveTenant($token);
        if (! $tenant) {
            abort(404);
        }

        $this->applyTenantLocale($tenant, $request->query('lang'));

        return view('landing.tenant-provisioning', [
            'token' => $token,
            'businessName' => $tenant->name,
            'statusUrl' => route('signup.provisioning.status', ['token' => $token]),
            'resendUrl' => route('signup.provisioning.resend', ['token' => $token]),
        ]);
    }

    public function status(string $token)
    {
        $tenant = $this->resolveTenant($token);
        if (! $tenant) {
            return response()->json([
                'success' => false,
                'message' => 'This provisioning link is invalid or expired.',
            ], 404);
        }

        $status = (string) ($tenant->provisioning_status ?? 'queued');
        $emailVerified = $this->emailIsVerified($tenant);

        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => (string) ($tenant->provisioning_message ?? 'Your workspace is being prepared.'),
            'ready' => $status === 'ready',
            'failed' => $status === 'failed',
            'email_verified' => $emailVerified,
            'verification_required' => ! $emailVerified,
            'redirect_url' => $status === 'ready' && $emailVerified
                ? $tenant->provisioning_redirect_url
                : null,
        ]);
    }

    public function verifyEmail(Request $request, string $token)
    {
        $tenant = $this->resolveEmailVerificationTenant($token);
        if (! $tenant) {
            abort(404);
        }

        $this->applyTenantLocale($tenant, $request->query('lang'));

        $tenant->update([
            'email_verified_at' => now(),
            'email_verification_token_used_at' => now(),
            'email_verification_token_hash' => null,
            'email_verification_expires_at' => null,
            'email_verification_token_encrypted' => null,
            'email_verification_url' => null,
            'provisioning_message' => ($tenant->provisioning_status ?? null) === 'ready'
                ? 'Email verified. Welcome to your workspace.'
                : 'Email verified. We are finishing your workspace.',
        ]);

        if (($tenant->provisioning_status ?? null) === 'ready' && ($handoffUrl = (string) $tenant->provisioning_redirect_url) !== '') {
            return redirect()->to($handoffUrl);
        }

        return view('landing.email-verified', [
            'businessName' => $tenant->name,
        ]);
    }

    public function resendVerification(Request $request, string $token)
    {
        $tenant = $this->resolveTenant($token);
        if (! $tenant) {
            abort(404);
        }

        if ($this->emailIsVerified($tenant)) {
            return response()->json(['success' => true, 'verified' => true]);
        }

        $key = 'tenant-email-verification:'.$request->ip().':'.$tenant->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another verification email.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $email = (string) ($tenant->provisioning_email ?? $tenant->email ?? '');
        if ($email === '') {
            return response()->json([
                'success' => false,
                'message' => 'Verification is not available for this workspace.',
            ], 422);
        }

        $verificationToken = $tenant->id.'.'.Str::random(64);
        $verificationUrl = url('/email/verify/'.$verificationToken);
        $expiresAt = now()->addHours(self::EMAIL_VERIFICATION_TTL_HOURS);

        $tenant->update([
            'email_verification_token_hash' => hash('sha256', $verificationToken),
            'email_verification_token_encrypted' => Crypt::encryptString($verificationToken),
            'email_verification_expires_at' => $expiresAt,
            'email_verification_url' => $verificationUrl,
            'email_verification_token_used_at' => null,
        ]);

        Mail::to($email)->queue(new VerifyTenantEmailMail(
            (string) $tenant->name,
            $tenant->id,
            (string) ($tenant->domains()->first()?->domain ?? ''),
            $verificationUrl,
            self::EMAIL_VERIFICATION_TTL_HOURS
        ));

        return response()->json(['success' => true, 'cooldown' => 60]);
    }

    public function handoff(Request $request, string $token)
    {
        $tenant = $this->resolveTenant($token);
        if (! $tenant || ($tenant->provisioning_status ?? null) !== 'ready') {
            abort(404);
        }

        if (! $this->emailIsVerified($tenant)) {
            return redirect()->route('signup.provisioning', ['token' => $token]);
        }

        $email = (string) ($tenant->provisioning_email ?? $tenant->email ?? '');
        $user = $tenant->run(fn () => \App\Models\User::where('email', $email)->first());
        if (! $user) {
            abort(404);
        }

        auth()->login($user);
        $request->session()->regenerate();
        $tenant->update(['provisioning_token_used_at' => now()]);

        return redirect('/admin/onboarding');
    }

    private function applyTenantLocale(Tenant $tenant, ?string $requestedLocale = null): void
    {
        $supported = array_values(array_unique(config('localizer.supported_locales', ['ar', 'en'])));
        $available = null;

        try {
            $settings = $tenant->settings;
            if ($settings?->available_languages) {
                $available = is_string($settings->available_languages)
                    ? json_decode($settings->available_languages, true)
                    : $settings->available_languages;
            }
        } catch (\Throwable) {
        }

        if (is_array($available) && $available !== []) {
            $supported = array_values(array_intersect($supported, $available));
        }

        if ($supported === []) {
            $supported = ['ar', 'en'];
        }

        // The tenant's signup language is persisted on the central tenant
        // record and must be the source of truth for central-domain pages
        // (verification/provisioning), where tenant DB settings are not yet
        // initialized as the active connection.
        $tenantDefault = $tenant->getAttribute('language');
        if (! is_string($tenantDefault) || $tenantDefault === '') {
            try {
                $tenantDefault = $tenant->settings?->language;
            } catch (\Throwable) {
                $tenantDefault = null;
            }
        }

        $locale = is_string($requestedLocale) && in_array($requestedLocale, $supported, true)
            ? $requestedLocale
            : (is_string($tenantDefault) && in_array($tenantDefault, $supported, true) ? $tenantDefault : config('app.locale', 'en'));

        if (! in_array($locale, $supported, true)) {
            $locale = $supported[0] ?? 'en';
        }

        App::setLocale($locale);
        session()->put('locale', $locale);
    }

    private function resolveTenant(string $token): ?Tenant
    {
        [$tenantId, $secret] = array_pad(explode('.', $token, 2), 2, null);
        if (! is_string($tenantId) || ! is_string($secret) || $tenantId === '' || strlen($secret) < 32) {
            return null;
        }

        $tenant = Tenant::withTrashed()->find($tenantId);
        if (! $tenant || ! hash_equals((string) ($tenant->provisioning_token_hash ?? ''), hash('sha256', $token))) {
            return null;
        }
        if ($tenant->deleted_at !== null || $tenant->provisioning_token_used_at) {
            return null;
        }

        $createdAt = $tenant->created_at;
        return ! $createdAt || $createdAt->gte(now()->subMinutes(self::TOKEN_TTL_MINUTES)) ? $tenant : null;
    }

    private function resolveEmailVerificationTenant(string $token): ?Tenant
    {
        [$tenantId, $secret] = array_pad(explode('.', $token, 2), 2, null);
        if (! is_string($tenantId) || ! is_string($secret) || $tenantId === '' || strlen($secret) < 48) {
            return null;
        }

        $tenant = Tenant::withTrashed()->find($tenantId);
        if (! $tenant || $tenant->deleted_at !== null) {
            return null;
        }

        $hash = (string) ($tenant->email_verification_token_hash ?? '');
        $expires = $tenant->email_verification_expires_at;
        if ($hash === '' || ! hash_equals($hash, hash('sha256', $token)) || ! $expires) {
            return null;
        }

        try {
            if (Carbon::parse($expires)->isPast()) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return $tenant->email_verification_token_used_at === null ? $tenant : null;
    }

    private function emailIsVerified(Tenant $tenant): bool
    {
        return $tenant->email_verified_at !== null;
    }
}
