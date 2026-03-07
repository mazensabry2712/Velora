<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\Tenant\AppointmentController;
use App\Http\Middleware\SetTenantLocale;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\QueueController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\AssistantController;
use App\Http\Controllers\Web\WaitingListController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\StaffScheduleController;
use App\Http\Controllers\Admin\BusinessRuleController;
use App\Http\Controllers\Admin\GdprController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ReminderRuleController;
use App\Http\Controllers\Admin\ReminderLogController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\RecurringController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerV2Controller;
use App\Http\Controllers\Admin\CommissionsController;
use App\Http\Controllers\Admin\PaymentTransactionController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\BillingController;
use App\Http\Middleware\EnsureSubscriptionIsValid;
use App\Http\Controllers\Admin\OnboardingController;

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
        $availableLanguages = $settings?->available_languages ?? ['en'];
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
    Route::get('/login', fn () => view('auth.login'))->name('login');

    // Auth API endpoints (for AJAX)
    Route::prefix('api/auth')->group(function () {
        Route::post('/login', [\App\Http\Controllers\Auth\TenantAuthController::class, 'login']);
        Route::post('/logout', [\App\Http\Controllers\Auth\TenantAuthController::class, 'logout'])->middleware('auth');
    });

    // Public API endpoints for booking form
    Route::prefix('api/booking')->group(function () {
        Route::get('/services',           [ServiceController::class, 'index']);
        Route::get('/timeslots',          [ServiceController::class, 'timeSlots']);
        Route::get('/available-timeslots', [ServiceController::class, 'availableTimeSlots']);
        Route::get('/workingdays',        [ServiceController::class, 'workingDays']);
        Route::get('/staff/{id}/services', [ServiceController::class, 'staffServices']);
        Route::get('/staff/by-service/{serviceId}', [StaffController::class, 'byService']);
        Route::get('/staff/{id}/schedule',  [StaffController::class, 'schedule']);
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
            Route::post('/billing/checkout',      [BillingController::class, 'checkout'])->name('billing.checkout');
            Route::post('/billing/portal',        [BillingController::class, 'portal'])->name('billing.portal');
            Route::post('/billing/extend-trial',  [BillingController::class, 'extendTrial'])->name('billing.extend-trial');
        });
    });

    // Admin Routes (Protected)
    Route::middleware(['auth', 'role:Admin Tenant|Staff|Assistant', EnsureSubscriptionIsValid::class, 'onboarding.redirect'])->prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Onboarding Wizard (shown on first login until completed)
        Route::get('/onboarding',              [OnboardingController::class, 'index'])->name('onboarding');
        Route::post('/onboarding/step1',        [OnboardingController::class, 'saveStep1'])->name('onboarding.step1');
        Route::post('/onboarding/step2',        [OnboardingController::class, 'saveStep2'])->name('onboarding.step2');
        Route::post('/onboarding/step3',        [OnboardingController::class, 'saveStep3'])->name('onboarding.step3');
        Route::post('/onboarding/complete',     [OnboardingController::class, 'complete'])->name('onboarding.complete');

        // Profile Management
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::delete('/profile', [ProfileController::class, 'deleteAccount'])->name('profile.delete');

        // Appointments Management
        Route::get('/appointments', [AdminAppointmentController::class, 'index'])->name('appointments');

        // Staff Management
        Route::get('/staff', [StaffController::class, 'index'])->name('staff');

        // Settings Page
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');

        // Queue Management
        Route::get('/queue',                [AdminQueueController::class, 'days'])->name('queue');
        Route::get('/queue/{date}',         [AdminQueueController::class, 'show'])->name('queue.day');
        Route::get('/queue/{date}/print',   [AdminQueueController::class, 'print'])->name('queue.print');
        Route::get('/queue/export-excel',   [AdminQueueController::class, 'exportExcel'])->name('queue.export.excel');

        // Customers Management Page
        Route::get('/customers', [CustomerController::class, 'adminPage'])->name('customers');

        // Reports
        Route::get('/reports',                      [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export-appointments',  [ReportController::class, 'exportAppointments'])->name('reports.export.appointments');

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
            // ── Appointments ───────────────────────────────────────────────
            Route::post('/appointments',                      [AdminAppointmentController::class, 'store'])->name('api.appointments.store');
            Route::get('/appointments/{id}',                  [AdminAppointmentController::class, 'show'])->name('api.appointments.show');
            Route::put('/appointments/{id}',                  [AdminAppointmentController::class, 'update'])->name('api.appointments.update');
            Route::delete('/appointments/{id}',               [AdminAppointmentController::class, 'destroy'])->name('api.appointments.destroy');
            Route::patch('/appointments/{id}/status',         [AdminAppointmentController::class, 'quickStatus'])->name('api.appointments.status');
            Route::patch('/appointments/{id}/quick-status',   [AdminAppointmentController::class, 'quickStatus'])->name('api.appointments.quickStatus');
            Route::post('/appointments/{id}/add-to-queue',    [AdminAppointmentController::class, 'addToQueue'])->name('api.appointments.addToQueue');
            Route::post('/appointments/{id}/remove-from-queue', [AdminAppointmentController::class, 'removeFromQueue'])->name('api.appointments.removeFromQueue');
            Route::post('/appointments/{id}/send-reminder',   [AdminAppointmentController::class, 'sendReminder'])->name('api.appointments.sendReminder');
            Route::post('/appointments/bulk-day-action',      [AdminAppointmentController::class, 'bulkDayAction'])->name('api.appointments.bulkDayAction');
            Route::get('/appointments/{id}/qrcode',           [AdminAppointmentController::class, 'generateQRCode'])->name('api.appointments.qrcode');
            Route::post('/appointments/{id}/rate',            [AdminAppointmentController::class, 'rate'])->name('api.appointments.rate');

            // ── Settings ───────────────────────────────────────────────────
            Route::post('/settings', [SettingController::class, 'save'])->name('api.settings.save');

            // ── Services ───────────────────────────────────────────────────
            Route::get('/settings/services/{id}',    [ServiceController::class, 'show'])->name('api.services.show');
            Route::post('/settings/services',         [ServiceController::class, 'store'])->name('api.services.store');
            Route::put('/settings/services/{id}',     [ServiceController::class, 'update'])->name('api.services.update');
            Route::delete('/settings/services/{id}',  [ServiceController::class, 'destroy'])->name('api.services.destroy');

            // ── Time Slots ─────────────────────────────────────────────────
            Route::post('/settings/timeslots',           [ServiceController::class, 'storeTimeSlot'])->name('api.timeslots.store');
            Route::post('/settings/timeslots/{id}/toggle', [ServiceController::class, 'toggleTimeSlot'])->name('api.timeslots.toggle');
            Route::delete('/settings/timeslots/{id}',    [ServiceController::class, 'destroyTimeSlot'])->name('api.timeslots.destroy');

            // ── Working Days ───────────────────────────────────────────────
            Route::post('/settings/working-days/{id}/toggle', [ServiceController::class, 'toggleWorkingDay'])->name('api.workingdays.toggle');

            // ── Staff-Service Assignment ───────────────────────────────────
            Route::post('/settings/staff-services/toggle', [ServiceController::class, 'toggleStaffService'])->name('api.staff.services.toggle');

            // ── Staff ──────────────────────────────────────────────────────
            Route::get('/staff/{id}',                               [StaffController::class, 'show'])->name('api.staff.show');
            Route::post('/staff',                                    [StaffController::class, 'store'])->name('api.staff.store');
            Route::put('/staff/{id}',                                [StaffController::class, 'update'])->name('api.staff.update');
            Route::delete('/staff/{id}',                             [StaffController::class, 'destroy'])->name('api.staff.destroy');
            Route::get('/staff/by-specialization/{specialization}',  [StaffController::class, 'bySpecialization'])->name('api.staff.by-specialization');
            Route::get('/staff/{id}/services',                       [StaffController::class, 'services'])->name('api.staff.services');

            // ── Queue ──────────────────────────────────────────────────────
            Route::post('/queue/add',                 [AdminQueueController::class, 'addDirect'])->name('api.queue.add');
            Route::post('/queue/call-next',           [AdminQueueController::class, 'callNext'])->name('api.queue.call-next');
            Route::post('/queue/move-next-day',       [AdminQueueController::class, 'moveToNextDay'])->name('api.queue.move-next-day');
            Route::get('/queue/{id}',                 [AdminQueueController::class, 'get'])->name('api.queue.get');
            Route::put('/queue/{id}',                 [AdminQueueController::class, 'updateEntry'])->name('api.queue.update');
            Route::delete('/queue/{id}',              [AdminQueueController::class, 'remove'])->name('api.queue.delete');
            Route::post('/queue/{id}/serve',          [AdminQueueController::class, 'serve'])->name('api.queue.serve');
            Route::post('/queue/{id}/complete',       [AdminQueueController::class, 'complete'])->name('api.queue.complete');
            Route::post('/queue/{id}/return-waiting', [AdminQueueController::class, 'returnToWaiting'])->name('api.queue.return-waiting');
            Route::post('/queue/{id}/priority',       [AdminQueueController::class, 'setPriority'])->name('api.queue.set-priority');

            // ── Customers ──────────────────────────────────────────────────
            Route::get('/customers',                   [CustomerController::class, 'adminIndex'])->name('api.customers.index');
            Route::get('/customers/{id}',              [CustomerController::class, 'adminShow'])->name('api.customers.show');
            Route::put('/customers/{id}/vip',          [CustomerController::class, 'toggleVip'])->name('api.customers.vip');
            Route::get('/customers/{id}/appointments', [CustomerController::class, 'getAppointments'])->name('api.customers.appointments');
            Route::delete('/customers/{id}',           [CustomerController::class, 'destroy'])->name('api.customers.destroy');

            // ── Customers V2 (Customer model — new in V2) ────────────────────
            Route::get('/v2/customers',                      [AdminCustomerV2Controller::class, 'index'])->name('api.v2.customers.index');
            Route::post('/v2/customers',                     [AdminCustomerV2Controller::class, 'store'])->name('api.v2.customers.store');
            Route::get('/v2/customers/{id}',                 [AdminCustomerV2Controller::class, 'show'])->name('api.v2.customers.show');
            Route::put('/v2/customers/{id}',                 [AdminCustomerV2Controller::class, 'update'])->name('api.v2.customers.update');
            Route::delete('/v2/customers/{id}',              [AdminCustomerV2Controller::class, 'destroy'])->name('api.v2.customers.destroy');
            Route::get('/v2/customers/{id}/appointments',    [AdminCustomerV2Controller::class, 'appointments'])->name('api.v2.customers.appointments');
            Route::patch('/v2/customers/{id}/block',         [AdminCustomerV2Controller::class, 'block'])->name('api.v2.customers.block');
            Route::patch('/v2/customers/{id}/unblock',       [AdminCustomerV2Controller::class, 'unblock'])->name('api.v2.customers.unblock');

            // ── Staff Commissions Report (Admin only) ────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/commissions',                  [CommissionsController::class, 'index'])->name('api.commissions.index');
                Route::get('/commissions/summary',          [CommissionsController::class, 'summary'])->name('api.commissions.summary');
                Route::patch('/commissions/{id}/mark-paid', [CommissionsController::class, 'markPaid'])->name('api.commissions.mark-paid');
                Route::post('/commissions/bulk-mark-paid',  [CommissionsController::class, 'bulkMarkPaid'])->name('api.commissions.bulk-mark-paid');
            });

            // ── Payment Transactions (Admin only) ────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/payments',                  [PaymentTransactionController::class, 'index'])->name('api.payments.index');
                Route::get('/payments/summary',          [PaymentTransactionController::class, 'summary'])->name('api.payments.summary');
                Route::get('/payments/{id}',             [PaymentTransactionController::class, 'show'])->name('api.payments.show');
                Route::patch('/payments/{id}/mark-paid', [PaymentTransactionController::class, 'markPaid'])->name('api.payments.mark-paid');
                Route::post('/payments/{id}/refund',     [PaymentTransactionController::class, 'refund'])->name('api.payments.refund');
            });

            // ── Assistants (Admin only) ────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/assistants',       [AssistantController::class, 'index'])->name('api.assistants.index');
                Route::get('/assistants/{id}',  [AssistantController::class, 'show'])->name('api.assistants.show');
                Route::post('/assistants',      [AssistantController::class, 'store'])->name('api.assistants.store');
                Route::put('/assistants/{id}',  [AssistantController::class, 'update'])->name('api.assistants.update');
                Route::delete('/assistants/{id}', [AssistantController::class, 'destroy'])->name('api.assistants.destroy');
            });
            // ── Holidays (Admin only) ──────────────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/holidays',          [HolidayController::class, 'index'])->name('api.holidays.index');
                Route::get('/holidays/upcoming', [HolidayController::class, 'upcoming'])->name('api.holidays.upcoming');
                Route::post('/holidays',         [HolidayController::class, 'store'])->name('api.holidays.store');
                Route::put('/holidays/{id}',     [HolidayController::class, 'update'])->name('api.holidays.update');
                Route::delete('/holidays/{id}',  [HolidayController::class, 'destroy'])->name('api.holidays.destroy');
            });

            // ── Service Categories ─────────────────────────────────────────────────
            Route::get('/service-categories',                  [ServiceCategoryController::class, 'index'])->name('api.service-categories.index');
            Route::post('/service-categories',                 [ServiceCategoryController::class, 'store'])->name('api.service-categories.store');
            Route::post('/service-categories/reorder',         [ServiceCategoryController::class, 'reorder'])->name('api.service-categories.reorder');
            Route::get('/service-categories/{id}',             [ServiceCategoryController::class, 'show'])->name('api.service-categories.show');
            Route::put('/service-categories/{id}',             [ServiceCategoryController::class, 'update'])->name('api.service-categories.update');
            Route::delete('/service-categories/{id}',          [ServiceCategoryController::class, 'destroy'])->name('api.service-categories.destroy');
            Route::patch('/service-categories/{id}/toggle',    [ServiceCategoryController::class, 'toggle'])->name('api.service-categories.toggle');

            // ── Staff Schedule (working hours, breaks, time-off) ──────────────────
            Route::get('/staff/{id}/working-hours',              [StaffScheduleController::class, 'workingHours'])->name('api.staff.working-hours');
            Route::put('/staff/{id}/working-hours',              [StaffScheduleController::class, 'saveWorkingHours'])->name('api.staff.working-hours.save');
            Route::get('/staff/{id}/breaks',                     [StaffScheduleController::class, 'breaks'])->name('api.staff.breaks');
            Route::post('/staff/{id}/breaks',                    [StaffScheduleController::class, 'storeBreak'])->name('api.staff.breaks.store');
            Route::put('/staff/{id}/breaks/{breakId}',           [StaffScheduleController::class, 'updateBreak'])->name('api.staff.breaks.update');
            Route::delete('/staff/{id}/breaks/{breakId}',        [StaffScheduleController::class, 'destroyBreak'])->name('api.staff.breaks.destroy');
            Route::get('/staff/{id}/time-off',                   [StaffScheduleController::class, 'timeOff'])->name('api.staff.time-off');
            Route::post('/staff/{id}/time-off',                  [StaffScheduleController::class, 'storeTimeOff'])->name('api.staff.time-off.store');
            Route::put('/staff/{id}/time-off/{timeOffId}',       [StaffScheduleController::class, 'updateTimeOff'])->name('api.staff.time-off.update');
            Route::delete('/staff/{id}/time-off/{timeOffId}',    [StaffScheduleController::class, 'destroyTimeOff'])->name('api.staff.time-off.destroy');
            Route::patch('/staff/{id}/time-off/{timeOffId}/status', [StaffScheduleController::class, 'approveTimeOff'])->name('api.staff.time-off.status');
            Route::get('/staff/{id}/commissions',                [StaffScheduleController::class, 'commissions'])->name('api.staff.commissions');

            // ── Business Rules ─────────────────────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/business-rules',         [BusinessRuleController::class, 'index'])->name('api.business-rules.index');
                Route::put('/business-rules',         [BusinessRuleController::class, 'update'])->name('api.business-rules.update');
                Route::get('/business-rules/{key}',   [BusinessRuleController::class, 'show'])->name('api.business-rules.show');
                Route::delete('/business-rules/{key}',[BusinessRuleController::class, 'destroy'])->name('api.business-rules.destroy');
            });

            // ── GDPR (Admin only) ─────────────────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/gdpr/consents',                         [GdprController::class, 'consents'])->name('api.gdpr.consents');
                Route::get('/gdpr/customers/{customerId}/consents',  [GdprController::class, 'customerConsents'])->name('api.gdpr.customer-consents');
                Route::post('/gdpr/customers/{customerId}/consents', [GdprController::class, 'recordConsent'])->name('api.gdpr.record-consent');
                Route::delete('/gdpr/customers/{customerId}/consents/{type}', [GdprController::class, 'revokeConsent'])->name('api.gdpr.revoke-consent');
                Route::post('/gdpr/customers/{customerId}/export',   [GdprController::class, 'requestExport'])->name('api.gdpr.export');
                Route::post('/gdpr/customers/{customerId}/delete',   [GdprController::class, 'requestDeletion'])->name('api.gdpr.delete');
            });

            // ── Analytics (Admin only) ─────────────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/analytics/summary',  [AnalyticsController::class, 'summary'])->name('api.analytics.summary');
                Route::get('/analytics/daily',    [AnalyticsController::class, 'daily'])->name('api.analytics.daily');
                Route::get('/analytics/heatmap',  [AnalyticsController::class, 'heatmap'])->name('api.analytics.heatmap');
                Route::get('/analytics/staff',    [AnalyticsController::class, 'staff'])->name('api.analytics.staff');
                Route::get('/analytics/services', [AnalyticsController::class, 'services'])->name('api.analytics.services');
            });

            // ── Reminder Rules (Admin only) ────────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/reminder-rules',                    [ReminderRuleController::class, 'index'])->name('api.reminder-rules.index');
                Route::post('/reminder-rules',                   [ReminderRuleController::class, 'store'])->name('api.reminder-rules.store');
                Route::post('/reminder-rules/reorder',           [ReminderRuleController::class, 'reorder'])->name('api.reminder-rules.reorder');
                Route::get('/reminder-rules/{id}',               [ReminderRuleController::class, 'show'])->name('api.reminder-rules.show');
                Route::put('/reminder-rules/{id}',               [ReminderRuleController::class, 'update'])->name('api.reminder-rules.update');
                Route::delete('/reminder-rules/{id}',            [ReminderRuleController::class, 'destroy'])->name('api.reminder-rules.destroy');
                Route::patch('/reminder-rules/{id}/toggle',      [ReminderRuleController::class, 'toggle'])->name('api.reminder-rules.toggle');
            });

            // ── Reminder Logs (Admin only) ─────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/reminder-logs',         [ReminderLogController::class, 'index'])->name('api.reminder-logs.index');
                Route::get('/reminder-logs/stats',   [ReminderLogController::class, 'stats'])->name('api.reminder-logs.stats');
                Route::get('/reminder-logs/{id}',    [ReminderLogController::class, 'show'])->name('api.reminder-logs.show');
            });

            // ── Resources (Admin only) ─────────────────────────────────────────────
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/resources',                              [ResourceController::class, 'index'])->name('api.resources.index');
                Route::post('/resources',                             [ResourceController::class, 'store'])->name('api.resources.store');
                Route::get('/resources/{id}',                        [ResourceController::class, 'show'])->name('api.resources.show');
                Route::put('/resources/{id}',                        [ResourceController::class, 'update'])->name('api.resources.update');
                Route::delete('/resources/{id}',                     [ResourceController::class, 'destroy'])->name('api.resources.destroy');
                Route::patch('/resources/{id}/toggle',               [ResourceController::class, 'toggle'])->name('api.resources.toggle');
                Route::post('/resources/{id}/services',              [ResourceController::class, 'attachServices'])->name('api.resources.services.attach');
                Route::delete('/resources/{id}/services/{serviceId}',[ResourceController::class, 'detachService'])->name('api.resources.services.detach');
            });

            // ── Recurring Appointments ──────────────────────────────────────────────
            Route::post('/recurring',                            [RecurringController::class, 'store'])->name('api.recurring.store');
            Route::get('/recurring/{ruleId}/appointments',       [RecurringController::class, 'appointments'])->name('api.recurring.appointments');
            Route::delete('/recurring/{ruleId}',                 [RecurringController::class, 'cancelSeries'])->name('api.recurring.cancel');

            // ── Invoices ───────────────────────────────────────────────────────────
            Route::get('/invoices',                               [InvoiceController::class, 'index'])->name('api.invoices.index');
            Route::post('/invoices',                              [InvoiceController::class, 'store'])->name('api.invoices.store');
            Route::get('/invoices/{id}',                          [InvoiceController::class, 'show'])->name('api.invoices.show');
            Route::put('/invoices/{id}',                          [InvoiceController::class, 'update'])->name('api.invoices.update');
            Route::patch('/invoices/{id}/status',                 [InvoiceController::class, 'updateStatus'])->name('api.invoices.status');
            Route::post('/invoices/{id}/items',                   [InvoiceController::class, 'storeItem'])->name('api.invoices.items.store');
            Route::delete('/invoices/{invoiceId}/items/{itemId}', [InvoiceController::class, 'destroyItem'])->name('api.invoices.items.destroy');
            Route::delete('/invoices/{id}',                       [InvoiceController::class, 'destroy'])->name('api.invoices.destroy');

            // ── Waiting List (Admin) ───────────────────────────────────────────────
            Route::get('/waiting-list',             [WaitingListController::class, 'adminIndex'])->name('api.waiting-list.index');
            Route::post('/waiting-list/{id}/notify', [WaitingListController::class, 'adminNotify'])->name('api.waiting-list.notify');
            Route::delete('/waiting-list/{id}',     [WaitingListController::class, 'adminDestroy'])->name('api.waiting-list.destroy');        });
    });

    // ── Public Waiting List API ────────────────────────────────────────────
    Route::prefix('api/waiting-list')->group(function () {
        Route::post('/join',          [WaitingListController::class, 'join']);
        Route::get('/status',         [WaitingListController::class, 'status']);
        Route::post('/{id}/cancel',   [WaitingListController::class, 'cancel']);
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
