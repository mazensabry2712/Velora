<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TenantPasswordResetMail;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;

final class TenantPasswordResetController extends Controller
{
    private const TOKEN_TTL_MINUTES = 60;

    public function create()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = Str::lower(trim((string) $request->string('email')));
        $key = $this->rateKey($request, $email);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors([
                'email' => __('passwords.throttled'),
            ])->withInput();
        }

        RateLimiter::hit($key, 900);

        $tenant = tenant();
        if (! $tenant) {
            return back()->withErrors(['email' => __('passwords.user')])->withInput();
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        // Do not reveal whether an email exists in this tenant.
        if (! $user || ! $user->email_verified_at) {
            return back()->with('status', __('password_reset.sent'));
        }

        $locale = $this->resolveLocale($user, $tenant);
        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => hash('sha256', $plainToken),
                'created_at' => now(),
            ],
        );

        $domain = $tenant->domains->first()?->domain ?: request()->getHost();
        $resetUrl = 'https://' . $domain . route('password.reset', [], false)
            . '?token=' . urlencode($plainToken)
            . '&email=' . urlencode($email);

        Mail::to($user->email)->queue(new TenantPasswordResetMail(
            $user->name,
            $resetUrl,
            $locale,
        ));

        return back()->with('status', __('password_reset.sent'));
    }

    public function edit(Request $request, string $token)
    {
        $email = Str::lower(trim((string) $request->query('email', '')));
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! hash_equals((string) $record->token, hash('sha256', $token)) || $this->expired($record->created_at)) {
            return redirect()->route('password.request')->withErrors([
                'email' => __('passwords.token'),
            ]);
        }

        return view('auth.reset-password', compact('token', 'email'));
    }

    public function update(Request $request, string $token)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = Str::lower(trim($data['email']));
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! hash_equals((string) $record->token, hash('sha256', $token)) || $this->expired($record->created_at)) {
            return back()->withErrors(['email' => __('passwords.token')])->withInput();
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->withErrors(['email' => __('passwords.user')])->withInput();
        }

        $user->forceFill(['password' => $data['password']])->save();
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        RateLimiter::clear($this->rateKey($request, $email));

        session()->put('locale', $this->resolveLocale($user, tenant()));

        return redirect()->route('login')->with('status', __('password_reset.reset_success'));
    }

    private function expired(?string $createdAt): bool
    {
        return ! $createdAt || CarbonImmutable::parse($createdAt)->addMinutes(self::TOKEN_TTL_MINUTES)->isPast();
    }

    private function rateKey(Request $request, string $email): string
    {
        return 'tenant-password-reset:' . (tenant()?->id ?? 'unknown') . ':' . $request->ip() . ':' . $email;
    }

    private function resolveLocale(User $user, $tenant): string
    {
        $supported = array_values(array_unique(config('localizer.supported_locales', ['ar', 'en'])));
        if (in_array($user->locale, $supported, true)) {
            return $user->locale;
        }
        if (is_string($tenant?->language) && in_array($tenant->language, $supported, true)) {
            return $tenant->language;
        }
        $publicDefault = SystemSetting::get('public_default_locale', 'ar');
        return in_array($publicDefault, $supported, true) ? $publicDefault : ($supported[0] ?? 'ar');
    }
}
