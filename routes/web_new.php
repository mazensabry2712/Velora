<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\SuperAdminController;

/*
|--------------------------------------------------------------------------
| Web Routes - Central Domain Only
|--------------------------------------------------------------------------
| These routes are for the central/main domain (booking-saas.test)
| Tenant routes are loaded from routes/tenant.php via bootstrap/app.php
*/

// Central Domain Routes - Redirect to Super Admin
Route::middleware('web')->group(function () {
    Route::get('/', function () {
        return redirect()->route('super-admin.login');
    });

    Route::get('/login', function () {
        return redirect()->route('super-admin.login');
    });
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
    Route::middleware(['auth:web', 'super.admin'])->group(function () {
        Route::get('/dashboard', function () {
            return view('super-admin.dashboard');
        })->name('dashboard');

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
