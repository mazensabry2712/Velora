<?php
file_put_contents(
    storage_path('logs/routes-test.log'),
    "web.php loaded\n",
    FILE_APPEND
);
use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\CountrySettingController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MoyasarWebhookController;
use App\Http\Controllers\PlanPriceController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SuperAdmin\CountryPricingController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\PromoCodeController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Middleware\SetCentralLocale;
use App\Models\ActivityLog;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes - Central Domain Only
|--------------------------------------------------------------------------
| velora.com  → Landing Page + Signup
| admin.velora.com → Super Admin (handled via prefix below)
| *.velora.com → Tenant routes (routes/tenant.php)
*/


Route::get('/probe', function () {
    return 'PROBE';
});




// ── Stripe Webhook (no CSRF, no middleware) ──────────────────────────────
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.stripe');

// ── Moyasar Webhook (no CSRF, no middleware) ─────────────────────────────
Route::post('/webhooks/moyasar', [MoyasarWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.moyasar');

// ── Landing + Marketing Routes ───────────────────────────────────────────────

// Route::middleware(['web', \App\Http\Middleware\SetCentralLocale::class, 'geo.detect', 'maintenance'])
//     ->domain(env('APP_DOMAIN', 'velora.test'))
//     ->group(function () {

Route::middleware(['web'])
    ->domain(env('APP_DOMAIN', 'velora.test'))
    ->group(function () {

        // Main landing page
        Route::get('/', [LandingController::class, 'index'])->name('landing');

        // Dedicated pricing page
        Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');

        // AJAX: set pricing country override (called by country-switcher)
        Route::post('/pricing/set-country', [PricingController::class, 'setCountry'])
            ->name('pricing.set-country')
            ->middleware('throttle:30,1');

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

                return redirect()->back(302, [], route('landing'))
                    ->withCookie(cookie()->forever('velora_locale_override', $locale));
            }

            return redirect(route('landing'));
        })->name('landing.lang');

        // Combined region+language switcher — sets both locale + country in one server hop
        // Hostinger-style: navigate here, cookies attach to the redirect response, browser stores them
        Route::get('/region/{locale}/{country}', function ($locale, $country) {
            $supported = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id'];
            $locale = in_array($locale, $supported) ? $locale : 'en';
            $code = strtoupper(preg_replace('/[^A-Za-z]/', '', $country));
            if (strlen($code) < 2 || strlen($code) > 10) {
                return redirect(route('landing'));
            }
            session()->put('central_locale', $locale);
            session(['pricing_country_override' => $code]);

            return redirect()->back(302, [], route('landing'))
                ->withCookie(cookie()->forever('velora_locale_override', $locale))
                ->withCookie(cookie()->forever('velora_country_override', $code));
        })->name('landing.region');

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
Route::prefix('super-admin')->name('super-admin.')->middleware([SetCentralLocale::class])->group(function () {

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
            $logs = ActivityLog::with('user:id,name')
                ->latest()
                ->paginate(5);

            $meta = [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'from' => $logs->firstItem() ?? 0,
                'to' => $logs->lastItem() ?? 0,
                'total' => $logs->total(),
                'prev_page_url' => $logs->previousPageUrl(),
                'next_page_url' => $logs->nextPageUrl(),
            ];

            $stats = [
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
            ];

            return view('super-admin.activity-logs', compact('logs', 'meta', 'stats'));
        })->name('activity-logs');

        Route::get('/settings', function () {
            return view('super-admin.settings');
        })->name('settings');

        Route::get('/notifications', function () {
            return view('super-admin.notifications');
        })->name('notifications');

        // ── Analytics (merged Reports + KPIs) ───────────────────────────────
        Route::get('/analytics', function () {
            return view('super-admin.analytics');
        })->name('analytics');

        // Legacy redirects – keep old URLs working
        Route::get('/reports', function () {
            return redirect()->route('super-admin.analytics', ['tab' => 'reports']);
        })->name('reports');

        Route::get('/kpis', function () {
            return redirect()->route('super-admin.analytics', ['tab' => 'kpis']);
        })->name('kpis');

        Route::get('/kpis/export.csv', [DashboardController::class, 'exportKpis'])->name('kpis.export');

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

        // ── Country Pricing ─────────────────────────────────────────────────
        Route::resource('country-pricing', CountryPricingController::class)
            ->names('country-pricing');
        Route::post('country-pricing/{countryPricing}/toggle', [CountryPricingController::class, 'toggleActive'])
            ->name('country-pricing.toggle');

        // ── Promo Codes ─────────────────────────────────────────────────────
        Route::get('/promo-codes', [PromoCodeController::class, 'index'])->name('promo-codes.index');
        Route::post('/promo-codes', [PromoCodeController::class, 'store'])->name('promo-codes.store');
        Route::patch('/promo-codes/{id}/toggle', [PromoCodeController::class, 'toggle'])->name('promo-codes.toggle');
        Route::delete('/promo-codes/{id}', [PromoCodeController::class, 'destroy'])->name('promo-codes.destroy');
    });

// Load tenant routes LAST — guarantees central domain routes above always match first
Route::domain('{tenantSubdomain}.' . env('APP_BASE_DOMAIN', 'velora.test'))
    ->middleware([
        'web',
        \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
    ])
    ->group(base_path('routes/tenant.php'));


    // @include __DIR__.'/tenant.php';
}
}
