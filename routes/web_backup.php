<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\QueueController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\AssistantController;
use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\SuperAdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
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

// ==========================================================================
// TENANT ROUTES ARE LOADED FROM routes/tenant.php
// ==========================================================================
// Tenant routes are loaded separately via bootstrap/app.php
// with proper tenancy middleware. Do NOT add tenant routes here.
// ==========================================================================

    // Get available languages from settings
    $settingsModel = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $availableLanguages = $settingsModel && $settingsModel->available_languages
        ? $settingsModel->available_languages
        : ['en', 'ar'];

    // Only allow switching to available languages
    if (in_array($lang, $availableLanguages)) {
        session()->put('locale', $lang);
        session()->save();
        app()->setLocale($lang);
    }
    return redirect()->back();
})->name('change.language');

// Public Customer Routes (Tenant-aware)
Route::middleware(['tenant', \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class, 'tenant.locale'])->group(function () {

    // Welcome/Home page
    Route::get('/', function () {
        return redirect()->route('customer.booking');
    });

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

    // Customer Booking
    Route::get('/book', [CustomerController::class, 'booking'])->name('customer.booking');
    Route::get('/my-queue', [CustomerController::class, 'myQueue'])->name('customer.my-queue');

    // Public Queue Dashboard
    Route::get('/queue', [QueueController::class, 'dashboard'])->name('queue.status');
});

// Admin Routes (Protected)
Route::middleware(['tenant', \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class, 'tenant.locale', 'auth', 'role:Admin Tenant|Staff|Assistant'])->prefix('admin')->name('admin.')->group(function () {

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

    // Subscription & Billing (Admin Only)
    Route::middleware(['role:Admin Tenant'])->group(function () {
        Route::get('/subscription', [\App\Http\Controllers\Web\SubscriptionController::class, 'index'])->name('subscription.index');
        Route::get('/subscription/upgrade', [\App\Http\Controllers\Web\SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
        Route::post('/subscription/upgrade', [\App\Http\Controllers\Web\SubscriptionController::class, 'requestUpgrade'])->name('subscription.requestUpgrade');
        Route::get('/subscription/billing', [\App\Http\Controllers\Web\SubscriptionController::class, 'billing'])->name('subscription.billing');
        Route::get('/subscription/usage', [\App\Http\Controllers\Web\SubscriptionController::class, 'getUsage'])->name('subscription.usage');
    });

    // Assistants Page (Admin Only)
    Route::get('/assistants', function () {
        if (!auth()->guard('web')->user()->isAdminTenant()) {
            abort(403);
        }
        return view('admin.assistants.index');
    })->name('assistants');

    // API endpoints for AJAX (inside admin context)
    Route::prefix('api')->group(function () {
        // Appointments
        Route::post('/appointments', [AdminController::class, 'storeAppointment'])
            ->middleware('subscription.limits:appointments')
            ->name('api.appointments.store');
        Route::post('/appointments/bulk-day-action', [AdminController::class, 'bulkDayAction'])->name('api.appointments.bulkDayAction');
        Route::get('/appointments/export-excel', [AdminController::class, 'exportAppointmentsExcel'])->name('api.appointments.export');
        Route::get('/appointments/{id}', [AdminController::class, 'showAppointment'])->name('api.appointments.show');
        Route::put('/appointments/{id}', [AdminController::class, 'updateAppointment'])->name('api.appointments.update');
        Route::delete('/appointments/{id}', [AdminController::class, 'destroyAppointment'])->name('api.appointments.destroy');
        Route::patch('/appointments/{id}/status', [AdminController::class, 'quickStatusUpdate'])->name('api.appointments.status');
        Route::post('/appointments/{id}/add-to-queue', [AdminController::class, 'addAppointmentToQueue'])->name('api.appointments.addToQueue');
        Route::delete('/appointments/{id}/remove-from-queue', [AdminController::class, 'removeFromQueue'])->name('api.appointments.removeFromQueue');
        Route::post('/appointments/{id}/send-reminder', [AdminController::class, 'sendReminder'])->name('api.appointments.sendReminder');
        Route::get('/appointments/{id}/qrcode', [AdminController::class, 'generateQRCode'])->name('api.appointments.qrcode');

        // Staff Management
        Route::get('/staff/{id}', [AdminController::class, 'showStaff'])->name('api.staff.show');
        Route::post('/staff', [AdminController::class, 'storeStaff'])
            ->middleware('subscription.limits:users')
            ->name('api.staff.store');
        Route::put('/staff/{id}', [AdminController::class, 'updateStaff'])->name('api.staff.update');
        Route::delete('/staff/{id}', [AdminController::class, 'destroyStaff'])->name('api.staff.destroy');
        Route::get('/staff/by-specialization/{specialization}', [AdminController::class, 'getStaffBySpecialization'])->name('api.staff.bySpecialization');
        Route::get('/staff/{id}/services', [AdminController::class, 'getStaffServicesJson'])->name('api.staff.services');

        // Queue Management
        Route::post('/queue/add', [AdminController::class, 'addToQueue'])->name('api.queue.add');
        Route::post('/queue/call-next', [AdminController::class, 'callNext'])->name('api.queue.callNext');
        Route::get('/queue/{id}', [AdminController::class, 'getQueue'])->name('api.queue.show');
        Route::put('/queue/{id}', [AdminController::class, 'updateQueue'])->name('api.queue.update');
        Route::post('/queue/{id}/serve', [AdminController::class, 'serveQueue'])->name('api.queue.serve');
        Route::post('/queue/{id}/return-waiting', [AdminController::class, 'returnToWaiting'])->name('api.queue.returnWaiting');
        Route::post('/queue/{id}/complete', [AdminController::class, 'completeQueue'])->name('api.queue.complete');
        Route::post('/queue/{id}/priority', [AdminController::class, 'setQueuePriority'])->name('api.queue.priority');
        Route::delete('/queue/{id}', [AdminController::class, 'removeQueue'])->name('api.queue.remove');
        Route::post('/queue/move-to-next-day', [AdminController::class, 'moveQueueToNextDay'])->name('api.queue.moveToNextDay');

        // Settings - Services
        Route::post('/settings/services', [AdminController::class, 'storeService'])->name('api.settings.services.store');
        Route::get('/settings/services/{id}', [AdminController::class, 'showService'])->name('api.settings.services.show');
        Route::put('/settings/services/{id}', [AdminController::class, 'updateService'])->name('api.settings.services.update');
        Route::delete('/settings/services/{id}', [AdminController::class, 'destroyService'])->name('api.settings.services.destroy');

        // Settings - Time Slots
        Route::post('/settings/timeslots', [AdminController::class, 'storeTimeSlot'])->name('api.settings.timeslots.store');
        Route::post('/settings/timeslots/{id}/toggle', [AdminController::class, 'toggleTimeSlot'])->name('api.settings.timeslots.toggle');
        Route::delete('/settings/timeslots/{id}', [AdminController::class, 'destroyTimeSlot'])->name('api.settings.timeslots.destroy');

        // Settings - Working Days
        Route::post('/settings/workingdays/{id}/toggle', [AdminController::class, 'toggleWorkingDay'])->name('api.settings.workingdays.toggle');

        // Settings - Staff Services
        Route::post('/settings/staff-services', [AdminController::class, 'toggleStaffService'])->name('api.settings.staffservices');

        // Business Settings
        Route::post('/settings', [AdminController::class, 'saveSettings'])->name('api.settings.save');

        // Assistants Management (Admin Only)
        Route::get('/assistants', [AssistantController::class, 'index'])->name('api.assistants.index');
        Route::get('/assistants/{id}', [AssistantController::class, 'show'])->name('api.assistants.show');
        Route::post('/assistants', [AssistantController::class, 'store'])->name('api.assistants.store');
        Route::put('/assistants/{id}', [AssistantController::class, 'update'])->name('api.assistants.update');
        Route::delete('/assistants/{id}', [AssistantController::class, 'destroy'])->name('api.assistants.destroy');
    });

    // Public API for dropdowns
    Route::prefix('api/public')->group(function () {
        Route::get('/services', [AdminController::class, 'getServices'])->name('api.public.services');
        Route::get('/timeslots', [AdminController::class, 'getTimeSlots'])->name('api.public.timeslots');
        Route::get('/workingdays', [AdminController::class, 'getWorkingDays'])->name('api.public.workingdays');
        Route::get('/staff/{id}/services', [AdminController::class, 'getStaffServices'])->name('api.public.staffservices');
    });

    // Queue Management
    Route::get('/queue', [AdminController::class, 'queueDays'])->name('queue');
    Route::get('/queue/print/{date?}', [AdminController::class, 'printQueue'])->name('queue.print');
    Route::get('/queue/export/excel', [AdminController::class, 'exportQueueToExcel'])->name('queue.export.excel');
    Route::get('/queue/{date}', [AdminController::class, 'queue'])->name('queue.day');

    // Reports
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
});

