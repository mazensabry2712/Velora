<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyTenantEmailMail;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class TenantProvisioningController extends Controller
{
    private const TOKEN_TTL_MINUTES = 30;
    private const EMAIL_VERIFICATION_TTL_HOURS = 24;

    public function show(string $token)
    {
        $tenant = $this->resolveTenant($token);
        if (! $tenant) {
            abort(404);
        }

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

    public function verifyEmail(string $token)
    {
        $tenant = $this->resolveEmailVerificationTenant($token);
        if (! $tenant) {
            abort(404);
        }

        $data = $this->tenantData($tenant);
        $data['email_verified_at'] = now()->toIso8601String();
        $data['email_verification_token_used_at'] = now()->toIso8601String();
        unset(
            $data['email_verification_token_hash'],
            $data['email_verification_expires_at'],
            $data['email_verification_url']
        );

        $tenant->update([
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'provisioning_message' => 'Email verified. Welcome to your workspace.',
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

        $data = $this->tenantData($tenant);
        $email = (string) ($data['provisioning_email'] ?? $data['email'] ?? '');
        if ($email === '') {
            return response()->json([
                'success' => false,
                'message' => 'Verification is not available for this workspace.',
            ], 422);
        }

        $verificationToken = $tenant->id.'.'.Str::random(64);
        $verificationUrl = url('/email/verify/'.$verificationToken);
        $data['email_verification_token_hash'] = hash('sha256', $verificationToken);
        $data['email_verification_expires_at'] = now()->addHours(self::EMAIL_VERIFICATION_TTL_HOURS)->toIso8601String();
        $data['email_verification_url'] = $verificationUrl;
        $data['email_verification_token_used_at'] = null;

        $tenant->update([
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        Mail::to($email)->queue(new VerifyTenantEmailMail(
            (string) ($data['name'] ?? $data['business_name'] ?? 'Tenant'),
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

        $data = $this->tenantData($tenant);
        if (empty($data['email_verified_at'])) {
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

        $data = $this->tenantData($tenant);
        $hash = (string) ($data['email_verification_token_hash'] ?? '');
        $expires = $data['email_verification_expires_at'] ?? null;
        if ($hash === '' || ! hash_equals($hash, hash('sha256', $token)) || ! is_string($expires)) {
            return null;
        }

        try {
            if (Carbon::parse($expires)->isPast()) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return empty($data['email_verification_token_used_at']) ? $tenant : null;
    }

    /** @return array<string, mixed> */
    private function tenantData(Tenant $tenant): array
    {
        $raw = $tenant->getRawOriginal('data');
        if (is_array($raw)) {
            return $raw;
        }
        return is_string($raw) ? (json_decode($raw, true) ?: []) : [];
    }

    private function emailIsVerified(Tenant $tenant): bool
    {
        return ! empty($this->tenantData($tenant)['email_verified_at']);
    }
}
