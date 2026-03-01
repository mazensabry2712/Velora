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
use App\Http\Controllers\BillingController;
use App\Http\Middleware\EnsureSubscriptionIsValid;

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
        $supported = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja'];
        if (in_array($lang, $supported)) {
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
        $settings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $availableLanguages = $settings->available_languages ?? ['en'];
        return view('customer.booking', compact('availableLanguages'));
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
    Route::get('/api/queue/status/{queueNumber}', [QueueController::class, 'getQueueStatus'])->name('api.queue.status');

    // Logout route (for forms)
    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->middleware('auth')->name('logout');

    // Customer routes
    Route::get('/my-queue', [CustomerController::class, 'myQueue'])->name('customer.my-queue');

    // ── Billing Routes (accessible without auth, but tenant-initialized) ────
    Route::middleware([EnsureSubscriptionIsValid::class])->group(function () {
        Route::get('/billing/expired', [BillingController::class, 'expired'])->name('billing.expired');
        Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
        Route::middleware(['auth'])->group(function () {
            Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
            Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
        });
    });

    // Admin Routes (Protected)
    Route::middleware(['auth', 'role:Admin Tenant|Staff|Assistant', EnsureSubscriptionIsValid::class])->prefix('admin')->name('admin.')->group(function () {

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
        Route::get('/queue/{date}/print', [AdminController::class, 'printQueue'])->name('queue.print');
        Route::get('/queue/export-excel', [AdminController::class, 'exportQueueToExcel'])->name('queue.export.excel');

        // Customers Management Page
        Route::get('/customers', [CustomerController::class, 'adminPage'])->name('customers');

        // Reports
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/reports/export-appointments', [AdminController::class, 'exportAppointmentsExcel'])->name('reports.export.appointments');

        // Assistants Management (Admin Only)
        Route::middleware(['role:Admin Tenant'])->get('/assistants', [AssistantController::class, 'page'])->name('assistants');

        // Subscription & Billing (Admin Only)
        Route::middleware(['role:Admin Tenant'])->group(function () {
            Route::get('/subscription', [BillingController::class, 'index'])->name('subscription.index');
            Route::get('/subscription/billing', [\App\Http\Controllers\Web\SubscriptionController::class, 'billing'])->name('subscription.billing');
            Route::get('/subscription/upgrade', [\App\Http\Controllers\Web\SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
            Route::post('/subscription/request-upgrade', [\App\Http\Controllers\Web\SubscriptionController::class, 'requestUpgrade'])->name('subscription.requestUpgrade');
        });

        // API endpoints for AJAX
        Route::prefix('api')->group(function () {
            // Appointments API
            Route::post('/appointments', [AdminController::class, 'storeAppointment'])->name('api.appointments.store');
            Route::get('/appointments/{id}', [AdminController::class, 'showAppointment'])->name('api.appointments.show');
            Route::put('/appointments/{id}', [AdminController::class, 'updateAppointment'])->name('api.appointments.update');
            Route::delete('/appointments/{id}', [AdminController::class, 'destroyAppointment'])->name('api.appointments.destroy');
            Route::patch('/appointments/{id}/status', [AdminController::class, 'updateAppointment'])->name('api.appointments.status');
            Route::post('/appointments/{id}/add-to-queue', [AdminController::class, 'addAppointmentToQueue'])->name('api.appointments.addToQueue');
            Route::post('/appointments/{id}/remove-from-queue', [AdminController::class, 'removeFromQueue'])->name('api.appointments.removeFromQueue');
            Route::post('/appointments/{id}/send-reminder', [AdminController::class, 'sendReminder'])->name('api.appointments.sendReminder');
            Route::post('/appointments/bulk-day-action', [AdminController::class, 'bulkDayAction'])->name('api.appointments.bulkDayAction');
            Route::get('/appointments/{id}/qrcode', [AdminController::class, 'generateQRCode'])->name('api.appointments.qrcode');
            Route::patch('/appointments/{id}/quick-status', [AdminController::class, 'quickStatusUpdate'])->name('api.appointments.quickStatus');
            Route::post('/appointments/{id}/rate', [AdminController::class, 'rateAppointment'])->name('api.appointments.rate');

            // Settings
            Route::post('/settings', [AdminController::class, 'saveSettings'])->name('api.settings.save');

            // Time Slots API
            Route::post('/settings/timeslots', [AdminController::class, 'storeTimeSlot'])->name('api.timeslots.store');
            Route::post('/settings/timeslots/{id}/toggle', [AdminController::class, 'toggleTimeSlot'])->name('api.timeslots.toggle');
            Route::delete('/settings/timeslots/{id}', [AdminController::class, 'destroyTimeSlot'])->name('api.timeslots.destroy');

            // Working Days API
            Route::post('/settings/working-days/{id}/toggle', [AdminController::class, 'toggleWorkingDay'])->name('api.workingdays.toggle');

            // Staff-Service Assignment
            Route::post('/settings/staff-services/toggle', [AdminController::class, 'toggleStaffService'])->name('api.staff.services.toggle');

            // Services API
            Route::get('/settings/services/{id}', [AdminController::class, 'showService'])->name('api.services.show');
            Route::post('/settings/services', [AdminController::class, 'storeService'])->name('api.services.store');
            Route::put('/settings/services/{id}', [AdminController::class, 'updateService'])->name('api.services.update');
            Route::delete('/settings/services/{id}', [AdminController::class, 'destroyService'])->name('api.services.destroy');

            // Staff API
            Route::get('/staff/{id}', [AdminController::class, 'showStaff'])->name('api.staff.show');
            Route::post('/staff', [AdminController::class, 'storeStaff'])->name('api.staff.store');
            Route::put('/staff/{id}', [AdminController::class, 'updateStaff'])->name('api.staff.update');
            Route::delete('/staff/{id}', [AdminController::class, 'destroyStaff'])->name('api.staff.destroy');
            Route::get('/staff/by-specialization/{specialization}', [AdminController::class, 'getStaffBySpecialization'])->name('api.staff.by-specialization');
            Route::get('/staff/{id}/services', [AdminController::class, 'getStaffServicesJson'])->name('api.staff.services');

            // Queue API
            Route::post('/queue/add', [AdminController::class, 'addToQueue'])->name('api.queue.add');
            Route::post('/queue/call-next', [AdminController::class, 'callNext'])->name('api.queue.call-next');
            Route::get('/queue/{id}', [AdminController::class, 'getQueue'])->name('api.queue.get');
            Route::put('/queue/{id}', [AdminController::class, 'updateQueue'])->name('api.queue.update');
            Route::delete('/queue/{id}', [AdminController::class, 'removeQueue'])->name('api.queue.delete');
            Route::post('/queue/{id}/serve', [AdminController::class, 'serveQueue'])->name('api.queue.serve');
            Route::post('/queue/{id}/complete', [AdminController::class, 'completeQueue'])->name('api.queue.complete');
            Route::post('/queue/{id}/return-waiting', [AdminController::class, 'returnToWaiting'])->name('api.queue.return-waiting');
            Route::post('/queue/{id}/priority', [AdminController::class, 'setQueuePriority'])->name('api.queue.set-priority');
            Route::post('/queue/move-next-day', [AdminController::class, 'moveQueueToNextDay'])->name('api.queue.move-next-day');

            // Customer Management
            Route::get('/customers', [CustomerController::class, 'adminIndex'])->name('api.customers.index');
            Route::get('/customers/{id}', [CustomerController::class, 'adminShow'])->name('api.customers.show');
            Route::put('/customers/{id}/vip', [CustomerController::class, 'toggleVip'])->name('api.customers.vip');
            Route::get('/customers/{id}/appointments', [CustomerController::class, 'getAppointments'])->name('api.customers.appointments');
            Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('api.customers.destroy');

            // Assistants Management (Admin only)
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/assistants', [AssistantController::class, 'index'])->name('api.assistants.index');
                Route::get('/assistants/{id}', [AssistantController::class, 'show'])->name('api.assistants.show');
                Route::post('/assistants', [AssistantController::class, 'store'])->name('api.assistants.store');
                Route::put('/assistants/{id}', [AssistantController::class, 'update'])->name('api.assistants.update');
                Route::delete('/assistants/{id}', [AssistantController::class, 'destroy'])->name('api.assistants.destroy');
            });
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
