<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\QueueController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\AssistantController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Change Language - Must be before other routes
Route::middleware(['tenant'])->get('/change-language/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'ar'])) {
        session()->put('locale', $lang);
        session()->save();
    }
    return redirect()->back();
})->name('change.language');

// Public Customer Routes (Tenant-aware)
Route::middleware(['tenant', 'tenant.locale'])->group(function () {

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
        auth()->logout();
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
Route::middleware(['tenant', 'tenant.locale', 'auth', 'role:Admin Tenant|Staff|Assistant'])->prefix('admin')->name('admin.')->group(function () {

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

    // Assistants Page (Admin Only)
    Route::get('/assistants', function () {
        if (!auth()->user()->isAdminTenant()) {
            abort(403);
        }
        return view('admin.assistants.index');
    })->name('assistants');

    // API endpoints for AJAX (inside admin context)
    Route::prefix('api')->group(function () {
        // Appointments
        Route::post('/appointments', [AdminController::class, 'storeAppointment'])->name('api.appointments.store');
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
        Route::post('/staff', [AdminController::class, 'storeStaff'])->name('api.staff.store');
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

