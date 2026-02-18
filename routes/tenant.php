<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\Tenant\AppointmentController;
use App\Http\Middleware\SetTenantLocale;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\QueueController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\AssistantController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

// All tenant routes must go through tenancy middleware
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Change Language - Must be OUTSIDE the locale middleware to avoid redirect loop
    Route::get('/change-language/{lang}', function ($lang) {
        if (in_array($lang, ['en', 'ar'])) {
            session()->put('locale', $lang);
            session()->save();
        }

        return redirect()->back();
    })->name('change.language');

    // Routes with locale middleware
    Route::middleware([SetTenantLocale::class])->group(function () {

    // Booking Form - Public Page
    Route::get('/', function () {
        return redirect()->route('customer.booking');
    });

    Route::get('/book', function () {
        return view('customer.booking');
    })->name('customer.booking');

    // Queue Status Page
    Route::get('/queue/status', function () {
        return view('customer.queue-status');
    })->name('customer.queue.status');

    // Alias for backward compatibility
    Route::get('/queue', function () {
        return redirect()->route('customer.queue.status');
    })->name('queue.status');

    // Login page
    Route::get('/login', [AdminController::class, 'login'])->name('login');

    // Auth API endpoints (for AJAX)
    Route::prefix('api/auth')->group(function () {
        Route::post('/login', [\App\Http\Controllers\Auth\TenantAuthController::class, 'login']);
        Route::post('/logout', [\App\Http\Controllers\Auth\TenantAuthController::class, 'logout'])->middleware('auth');
    });

    // Public API endpoints for booking form
    Route::prefix('api/booking')->group(function () {
        Route::get('/services', [AdminController::class, 'getServices']);
        Route::get('/timeslots', [AdminController::class, 'getTimeSlots']);
        Route::get('/available-timeslots', [AdminController::class, 'getAvailableTimeSlots']);
        Route::get('/workingdays', [AdminController::class, 'getWorkingDays']);
        Route::get('/staff/{id}/services', [AdminController::class, 'getStaffServices']);
        Route::get('/staff/by-service/{serviceId}', [AdminController::class, 'getStaffByService']);
        Route::get('/staff/{id}/schedule', [AdminController::class, 'getStaffSchedule']);
    });

    // Public Queue API (no auth required)
    Route::get('/api/queue', [QueueController::class, 'publicQueue'])->name('api.queue.public');

    // Logout route (for forms)
    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->middleware('auth')->name('logout');

    // Customer routes
    Route::get('/my-queue', [CustomerController::class, 'myQueue'])->name('customer.my-queue');

    // Admin Routes (Protected)
    Route::middleware(['auth', 'role:Admin Tenant|Staff|Assistant'])->prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // Profile Management
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::delete('/profile', [ProfileController::class, 'deleteAccount'])->name('profile.delete');

        // Appointments Management
        Route::get('/appointments', [AdminController::class, 'appointments'])->name('appointments');

        // Staff Management
        Route::get('/staff', [AdminController::class, 'staff'])->name('staff');

        // Settings Page
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');

        // Queue Management
        Route::get('/queue', [AdminController::class, 'queueDays'])->name('queue');
        Route::get('/queue/{date}', [AdminController::class, 'queue'])->name('queue.day');

        // Reports
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');

        // Subscription & Billing (Admin Only)
        Route::middleware(['role:Admin Tenant'])->group(function () {
            Route::get('/subscription', [\App\Http\Controllers\Web\SubscriptionController::class, 'index'])->name('subscription.index');
        });

        // API endpoints for AJAX
        Route::prefix('api')->group(function () {
            // Appointments API
            Route::post('/appointments', [AdminController::class, 'storeAppointment'])->name('api.appointments.store');
            Route::get('/appointments/{id}', [AdminController::class, 'showAppointment'])->name('api.appointments.show');
            Route::put('/appointments/{id}', [AdminController::class, 'updateAppointment'])->name('api.appointments.update');
            Route::delete('/appointments/{id}', [AdminController::class, 'destroyAppointment'])->name('api.appointments.destroy');

            // Settings
            Route::post('/settings', [AdminController::class, 'saveSettings'])->name('api.settings.save');
        });
    });

    // Public API Routes
    Route::prefix('api')->group(function () {
        // Get staff list
        Route::get('staff', function () {
            $staffRole = \App\Models\Role::where('name', 'Staff')->first();
            if (!$staffRole) {
                return response()->json([]);
            }
            return \App\Models\User::where('role_id', $staffRole->id)
                ->select('id', 'name')
                ->get();
        });

        // Create appointment (public)
        Route::post('appointments', [AppointmentController::class, 'store']);
    });
    }); // End SetTenantLocale middleware
}); // End tenancy middleware
