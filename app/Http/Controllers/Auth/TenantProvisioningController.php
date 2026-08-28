<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

final class TenantProvisioningController extends Controller
{
    private const TOKEN_TTL_MINUTES = 30;

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

        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => $message,
            'ready' => $status === 'ready',
            'failed' => $status === 'failed',
            'redirect_url' => $status === 'ready' ? $tenant->provisioning_redirect_url : null,
        ]);
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

        if ($tenant->deleted_at !== null) {
            return null;
        }

        if ($tenant->provisioning_token_used_at) {
            return null;
        }

        $createdAt = $tenant->created_at;
        if ($createdAt && $createdAt->lt(now()->subMinutes(self::TOKEN_TTL_MINUTES))) {
            return null;
        }

        return $tenant;
    }

    public function handoff(Request $request, string $token)
    {
        $tenant = $this->resolveTenant($token);

        if (! $tenant || ($tenant->provisioning_status ?? null) !== 'ready') {
            abort(404);
        }

        $email = (string) ($tenant->provisioning_email ?? $tenant->email ?? '');
        $user = $tenant->run(fn () => User::where('email', $email)->first());

        if (! $user) {
            abort(404);
        }

        auth()->login($user);
        $request->session()->regenerate();

        $tenant->update(['provisioning_token_used_at' => now()->toIso8601String()]);

        return redirect('/admin/onboarding');
    }
}
