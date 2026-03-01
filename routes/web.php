<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SuperAdminAuthController;
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

// ── Landing + Marketing Routes ───────────────────────────────────────────
Route::middleware('web')
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

    // Named login route for auth redirects (redirect to super-admin)
    Route::get('/login', function () {
        return redirect()->route('super-admin.login');
    })->name('central.login');
});

// Super Admin Routes (Central - No Tenant)
Route::prefix('super-admin')->name('super-admin.')->group(function () {

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
            return view('super-admin.activity-logs');
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

        // Upgrade Requests Management
        Route::get('/upgrade-requests', [SuperAdminController::class, 'upgradeRequests'])->name('upgrade-requests');
        Route::get('/upgrade-requests/{id}', [SuperAdminController::class, 'showUpgradeRequest'])->name('upgrade-requests.show');
        Route::post('/upgrade-requests/{id}/approve', [SuperAdminController::class, 'approveUpgrade'])->name('upgrade-requests.approve');
        Route::post('/upgrade-requests/{id}/reject', [SuperAdminController::class, 'rejectUpgrade'])->name('upgrade-requests.reject');
    });
});
