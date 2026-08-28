<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyTenantEmailMail;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

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
        $message = (string) ($tenant->provisioning_message ?? 'Your workspace is being prepared.');
        $emailVerified = $this->emailIsVerified($tenant);

        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => $message,
            'ready' => $status === 'ready',
            'failed' => $status === 'failed',
            'email_verified' => $emailVerified,
            'verification_required' => $status === 'ready' && ! $emailVerified,
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
        $email = (string) ($data['provisioning_email'] ?? $data['email'] ?? '');
        $user = $tenant->run(fn () => User::where('email', $email)->first());

        if (! $user) {
            abort(404);
        }

        if ($user->email_verified_at === null) {
            $tenant->run(function () use ($user): void {
                $user->forceFill(['email_verified_at' => now()])->save();
            });
        }

        unset(
            $data['email_verification_token_hash'],
            $data['email_verification_expires_at'],
            $data['email_verification_url']
        );
        $data['email_verification_token_used_at'] = now()->toIso8601String();
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

        $data = $this->tenantData($tenant);
        $verificationExpiresAt = isset($data['email_verification_expires_at'])
            ? Carbon::parse((string) $data['email_verification_expires_at'])
            : null;

        if (! $verificationExpiresAt || $verificationExpiresAt->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Your verification link has expired. Please contact support to restart verification.',
            ], 410);
        }

        $key = 'tenant-email-verification:'.$request->ip().':'.$tenant->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another verification email.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $email = (string) ($data['provisioning_email'] ?? $data['email'] ?? '');
        $verificationUrl = (string) ($data['email_verification_url'] ?? '');
        if ($email === '' || $verificationUrl === '') {
            return response()->json([
                'success' => false,
                'message' => 'Verification is not available for this workspace.',
            ], 422);
        }

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

        $email = (string) ($tenant->provisioning_email ?? $tenant->email ?? '');
        $user = $tenant->run(fn () => User::where('email', $email)->first());

        if (! $user || $user->email_verified_at === null) {
            return redirect()->route('signup.provisioning', ['token' => $token]);
        }

        auth()->login($user);
        $request->session()->regenerate();

        $tenant->update(['provisioning_token_used_at' => now()->toIso8601String()]);

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
        if ($createdAt && $createdAt->lt(now()->subMinutes(self::TOKEN_TTL_MINUTES))) {
            return null;
        }

        return $tenant;
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

        if ($hash === '' || ! hash_equals($hash, hash('sha256', $token))) {
            return null;
        }

        if (! is_string($expires) || Carbon::parse($expires)->isPast()) {
            return null;
        }

        return $tenant;
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
        $data = $this->tenantData($tenant);
        $email = (string) ($data['provisioning_email'] ?? $data['email'] ?? '');
        if ($email === '' || $tenant->provisioning_status !== 'ready') {
            return false;
        }

        $user = $tenant->run(fn () => User::where('email', $email)->first());
        return $user?->email_verified_at !== null;
    }
}
