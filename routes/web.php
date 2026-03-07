<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\CountrySettingController;
use App\Http\Controllers\PlanPriceController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Auth\TenantRegistrationController;

/*
|--------------------------------------------------------------------------
| Web Routes - Central Domain Only
|--------------------------------------------------------------------------
| velora.com  → Landing Page + Signup
| admin.velora.com → Super Admin (handled via prefix below)
| *.velora.com → Tenant routes (routes/tenant.php)
*/

// ── Stripe Webhook (no CSRF, no middleware) ──────────────────────────────
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.stripe');

// ── Moyasar Webhook (no CSRF, no middleware) ─────────────────────────────
Route::post('/webhooks/moyasar', [\App\Http\Controllers\MoyasarWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.moyasar');

// ── Landing + Marketing Routes ───────────────────────────────────────────
Route::middleware(['web', \App\Http\Middleware\SetCentralLocale::class, 'geo.detect'])
    ->domain(env('APP_DOMAIN', 'velora.test'))
    ->group(function () {
    // Main landing page
    Route::get('/', [LandingController::class, 'index'])->name('landing');

    // Pricing page
    Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');

    // Signup page
    Route::get('/signup', [LandingController::class, 'signup'])->name('signup');

    // Subdomain availability check (AJAX)
    Route::get('/signup/check-subdomain', [LandingController::class, 'checkSubdomain'])
         ->name('signup.check-subdomain')
         ->middleware('throttle:60,1');

    // Signup form submission
    Route::post('/signup', [TenantRegistrationController::class, 'store'])
         ->name('signup.store')
         ->middleware('throttle:10,1');

    // Central login: tenant owners find their salon by subdomain
    Route::get('/login', function () {
        return view('landing.find-account', [
            'baseDomain' => config('app.base_domain', 'velora.com'),
        ]);
    })->name('central.login');

    // Language switcher for landing / marketing pages
    Route::get('/lang/{locale}', function ($locale) {
        $supported = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id'];
        if (in_array($locale, $supported)) {
            session()->put('central_locale', $locale);
            cookie()->queue(cookie()->forever('velora_locale_override', $locale));
        }
        return redirect()->back();
    })->name('landing.lang');

    // Currency switcher for landing / marketing pages
    Route::get('/currency/{currency}', function ($currency) {
        $currency = strtoupper($currency);
        if (strlen($currency) === 3 && ctype_alpha($currency)) {
            session()->put('current_currency', $currency);
            cookie()->queue(cookie()->forever('velora_currency_override', $currency));
        }
        return redirect()->back();
    })->name('landing.currency');
});

// Super Admin Routes (Central - No Tenant)
Route::prefix('super-admin')->name('super-admin.')->middleware([\App\Http\Middleware\SetCentralLocale::class])->group(function () {

    // Login page
    Route::get('/login', function () {
        if (auth()->guard('web')->check() && auth()->guard('web')->user()->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }
        return view('super-admin.login');
    })->name('login');

    // Auth actions
    Route::post('/login', [SuperAdminAuthController::class, 'webLogin'])->name('login.post');
    Route::post('/logout', [SuperAdminAuthController::class, 'webLogout'])->name('logout');

    // Language switcher for super-admin panel
    Route::get('/lang/{locale}', function ($locale) {
        $supported = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id'];
        if (in_array($locale, $supported)) {
            session()->put('central_locale', $locale);
        }
        return redirect()->back();
    })->name('lang');

    // Protected Super Admin routes
    Route::middleware(['super.admin.auth'])->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/tenants', function () {
            return view('super-admin.tenants');
        })->name('tenants');

        Route::get('/subscription-plans', function () {
            return view('super-admin.subscription-plans');
        })->name('subscription-plans');

        Route::get('/activity-logs', function () {
            $logs = \App\Models\ActivityLog::with('user:id,name')
                ->latest()
                ->paginate(5);

            $meta = [
                'current_page'  => $logs->currentPage(),
                'last_page'     => $logs->lastPage(),
                'from'          => $logs->firstItem() ?? 0,
                'to'            => $logs->lastItem() ?? 0,
                'total'         => $logs->total(),
                'prev_page_url' => $logs->previousPageUrl(),
                'next_page_url' => $logs->nextPageUrl(),
            ];

            $stats = [
                'today'      => \App\Models\ActivityLog::whereDate('created_at', today())->count(),
                'this_week'  => \App\Models\ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => \App\Models\ActivityLog::whereMonth('created_at', now()->month)->count(),
            ];

            return view('super-admin.activity-logs', compact('logs', 'meta', 'stats'));
        })->name('activity-logs');

        Route::get('/settings', function () {
            return view('super-admin.settings');
        })->name('settings');

        Route::get('/notifications', function () {
            return view('super-admin.notifications');
        })->name('notifications');

        Route::get('/reports', function () {
            return view('super-admin.reports');
        })->name('reports');

        Route::get('/kpis', function () {
            return view('super-admin.kpis');
        })->name('kpis');

        Route::get('/kpis/export.csv', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'exportKpis'])->name('kpis.export');

        // Upgrade Requests Management
        Route::get('/upgrade-requests', [SuperAdminController::class, 'upgradeRequests'])->name('upgrade-requests');
        Route::get('/upgrade-requests/{id}', [SuperAdminController::class, 'showUpgradeRequest'])->name('upgrade-requests.show');
        Route::post('/upgrade-requests/{id}/approve', [SuperAdminController::class, 'approveUpgrade'])->name('upgrade-requests.approve');
        Route::post('/upgrade-requests/{id}/reject', [SuperAdminController::class, 'rejectUpgrade'])->name('upgrade-requests.reject');

        // ── Geo / Country Management ────────────────────────────────────────
        Route::resource('countries', CountrySettingController::class);

        // ── Plan Geo Pricing ────────────────────────────────────────────────
        Route::get('/subscription-plans/{plan}/prices', [PlanPriceController::class, 'index'])
            ->name('plan-prices.index');
        Route::post('/subscription-plans/{plan}/prices', [PlanPriceController::class, 'store'])
            ->name('plan-prices.store');
        Route::put('/subscription-plans/{plan}/prices/{planPrice}', [PlanPriceController::class, 'update'])
            ->name('plan-prices.update');
        Route::delete('/subscription-plans/{plan}/prices/{planPrice}', [PlanPriceController::class, 'destroy'])
            ->name('plan-prices.destroy');
    });
});
