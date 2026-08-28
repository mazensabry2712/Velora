<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\BusinessRuleController;
use App\Http\Controllers\Admin\CommissionsController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerV2Controller;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GdprController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\PaymentTransactionController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\RecurringController;
use App\Http\Controllers\Admin\ReminderLogController;
use App\Http\Controllers\Admin\ReminderRuleController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffScheduleController;
use App\Http\Controllers\Auth\TenantAuthController;
use App\Http\Controllers\Auth\TenantProvisioningController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\Tenant\AppointmentController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Web\AssistantController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\QueueController;
use App\Http\Controllers\Web\SubscriptionController;
use App\Http\Controllers\Web\WaitingListController;
use App\Http\Middleware\EnsureSubscriptionIsValid;
use App\Http\Middleware\SetTenantLocale;
use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // One-time tenant-domain handoff used after provisioning/email verification.
    Route::get('/__velora/provisioning/{token}', [TenantProvisioningController::class, 'handoff'])
        ->name('tenant.provisioning.handoff');

    Route::get('/change-language/{lang}', function ($lang) {
        $supported = array_values(array_unique(
            config('localizer.supported_locales', ['ar', 'en'])
        ));

        if (! in_array($lang, $supported, true)) {
            abort(404);
        }

        // Guests can keep a tenant-domain language choice in session.
        // Authenticated users persist the choice on their tenant user record,
        // making it survive logout/login and browser session changes.
        if (auth()->check()) {
            $user = auth()->user();
            $user->forceFill(['locale' => $lang])->save();
        }

        session()->put('locale', $lang);
        session()->save();
        App::setLocale($lang);

        return redirect()->back();
    })->name('tenant.change.language');

    Route::middleware([SetTenantLocale::class])->group(function () {
        Route::get('/', fn () => redirect('/book'));

        Route::get('/book', function () {
            $settings = Setting::where('tenant_id', tenant()->id)->first();
            $availableLanguages = $settings?->available_languages ?? config('localizer.supported_locales', ['ar', 'en']);
            return view('customer.booking', compact('availableLanguages'));
        })->name('customer.booking');

        Route::get('/queue/status', fn () => view('customer.queue-status'))->name('customer.queue.status');
        Route::get('/queue', fn () => redirect()->route('customer.queue.status'))->name('queue.status');
        Route::get('/login', fn () => view('auth.login'))->name('login');

        Route::prefix('api/auth')->group(function () {
            Route::post('/login', [TenantAuthController::class, 'login']);
            Route::post('/logout', [TenantAuthController::class, 'logout'])->middleware('auth');
        });

        Route::prefix('api/booking')->group(function () {
            Route::get('/services', [ServiceController::class, 'index']);
            Route::get('/timeslots', [ServiceController::class, 'timeSlots']);
            Route::get('/available-timeslots', [ServiceController::class, 'availableTimeSlots']);
            Route::get('/workingdays', [ServiceController::class, 'workingDays']);
            Route::get('/staff/{id}/services', [StaffController::class, 'staffServices']);
            Route::get('/staff/by-service/{serviceId}', [ServiceController::class, 'byService']);
            Route::get('/staff/{id}/schedule', [StaffController::class, 'schedule']);
        });

        Route::get('/api/queue', [QueueController::class, 'publicQueue'])->name('api.queue.public');
        Route::get('/api/queue/status/{queueNumber}', [QueueController::class, 'getQueueStatus'])->name('api.queue.status');

        Route::post('/api/appointments', [\App\Http\Controllers\Web\BookingController::class, 'store'])
            ->middleware(EnsureSubscriptionIsValid::class)
            ->name('api.appointments.public');

        Route::post('/logout', function () {
            auth()->guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login');
        })->middleware('auth')->name('logout');

        Route::get('/my-queue', [CustomerController::class, 'myQueue'])->name('customer.my-queue');

        Route::middleware([EnsureSubscriptionIsValid::class])->group(function () {
            Route::get('/billing/expired', [BillingController::class, 'expired'])->name('billing.expired');
            Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
            Route::get('/billing/moyasar/pay', [BillingController::class, 'moyasarPay'])->name('billing.moyasar.pay');
            Route::get('/billing/moyasar/callback', [BillingController::class, 'moyasarCallback'])->name('billing.moyasar.callback');

            Route::middleware(['auth'])->group(function () {
                Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
                Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
                Route::post('/billing/extend-trial', [BillingController::class, 'billing.extend-trial']);
            });
        });

        Route::middleware(['auth', 'role:Admin Tenant|Staff|Assistant', EnsureSubscriptionIsValid::class, 'onboarding.redirect'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
            Route::post('/onboarding/step1', [OnboardingController::class, 'saveStep1'])->name('onboarding.step1');
            Route::post('/onboarding/step2', [OnboardingController::class, 'saveStep2'])->name('onboarding.step2');
            Route::post('/onboarding/step3', [OnboardingController::class, 'saveStep3'])->name('onboarding.step3');
            Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
            Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
            Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
            Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
            Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
            Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
            Route::delete('/profile', [ProfileController::class, 'deleteAccount'])->name('profile.delete');
            Route::get('/appointments', [AdminAppointmentController::class, 'index'])->name('appointments');
            Route::get('/staff', [StaffController::class, 'index'])->name('staff');
            Route::get('/settings', [SettingController::class, 'index'])->name('settings');
            Route::get('/queue', [AdminQueueController::class, 'days'])->name('queue');
            Route::get('/queue/{date}', [AdminQueueController::class, 'show'])->name('queue.day');
            Route::get('/queue/{date}/print', [AdminQueueController::class, 'print'])->name('queue.print');
            Route::get('/queue/export-excel', [AdminQueueController::class, 'exportExcel'])->name('queue.export.excel');
            Route::get('/customers', [CustomerController::class, 'adminPage'])->name('customers');
            Route::get('/reports', [ReportController::class, 'index'])->name('reports');
            Route::get('/reports/export-appointments', [ReportController::class, 'exportAppointments'])->name('reports.export.appointments');
            Route::middleware(['role:Admin Tenant'])->get('/assistants', [AssistantController::class, 'page'])->name('assistants');
            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
                Route::get('/subscription/billing', [SubscriptionController::class, 'billing'])->name('subscription.billing');
                Route::get('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
                Route::post('/subscription/request-upgrade', [SubscriptionController::class, 'requestUpgrade'])->name('subscription.requestUpgrade');
            });

            Route::prefix('api')->group(function () {
                Route::post('/appointments', [AdminAppointmentController::class, 'store'])->name('api.appointments.store');
                Route::get('/appointments/{id}', [AdminAppointmentController::class, 'show'])->name('api.appointments.show');
                Route::put('/appointments/{id}', [AdminAppointmentController::class, 'update'])->name('api.appointments.update');
                Route::delete('/appointments/{id}', [AdminAppointmentController::class, 'destroy'])->name('api.appointments.destroy');
                Route::patch('/appointments/{id}/status', [AdminAppointmentController::class, 'quickStatus'])->name('api.appointments.status');
                Route::patch('/appointments/{id}/quick-status', [AdminAppointmentController::class, 'quickStatus'])->name('api.appointments.quickStatus');
                Route::post('/appointments/{id}/add-to-queue', [AdminAppointmentController::class, 'addToQueue'])->name('api.appointments.addToQueue');
                Route::post('/appointments/{id}/remove-from-queue', [AdminAppointmentController::class, 'removeFromQueue'])->name('api.appointments.removeFromQueue');
                Route::post('/appointments/{id}/send-reminder', [AdminAppointmentController::class, 'sendReminder'])->name('api.appointments.sendReminder');
                Route::post('/appointments/bulk-day-action', [AdminAppointmentController::class, 'bulkDayAction'])->name('api.appointments.bulkDayAction');
                Route::get('/appointments/{id}/qrcode', [AdminAppointmentController::class, 'generateQRCode'])->name('api.appointments.qrcode');
                Route::post('/appointments/{id}/rate', [AdminAppointmentController::class, 'rate'])->name('api.appointments.rate');
                Route::post('/settings', [SettingController::class, 'save'])->name('api.settings.save');
                Route::get('/settings/services/{id}', [ServiceController::class, 'show'])->name('api.services.show');
                Route::post('/settings/services', [ServiceController::class, 'store'])->name('api.services.store');
                Route::put('/settings/services/{id}', [ServiceController::class, 'update'])->name('api.services.update');
                Route::delete('/settings/services/{id}', [ServiceController::class, 'destroy'])->name('api.services.destroy');
                Route::post('/settings/timeslots', [ServiceController::class, 'storeTimeSlot'])->name('api.timeslots.store');
                Route::post('/settings/timeslots/{id}/toggle', [ServiceController::class, 'toggleTimeSlot'])->name('api.timeslots.toggle');
                Route::delete('/settings/timeslots/{id}', [ServiceController::class, 'destroyTimeSlot'])->name('api.timeslots.destroy');
                Route::post('/settings/working-days/{id}/toggle', [ServiceController::class, 'toggleWorkingDay'])->name('api.workingdays.toggle');
                Route::post('/settings/staff-services/toggle', [ServiceController::class, 'toggleStaffService'])->name('api.staff.services.toggle');
                Route::get('/staff/{id}', [StaffController::class, 'show'])->name('api.staff.show');
                Route::post('/staff', [StaffController::class, 'store'])->name('api.staff.store');
                Route::put('/staff/{id}', [StaffController::class, 'update'])->name('api.staff.update');
                Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('api.staff.destroy');
                Route::get('/staff/by-specialization/{specialization}', [StaffController::class, 'bySpecialization'])->name('api.staff.by-specialization');
                Route::get('/staff/{id}/services', [StaffController::class, 'services'])->name('api.staff.services');
                Route::post('/queue/add', [AdminQueueController::class, 'addDirect'])->name('api.queue.add');
                Route::post('/queue/call-next', [AdminQueueController::class, 'callNext'])->name('api.queue.call-next');
                Route::post('/queue/move-next-day', [AdminQueueController::class, 'moveToNextDay'])->name('api.queue.move-next-day');
                Route::get('/queue/{id}', [AdminQueueController::class, 'get'])->name('api.queue.get');
                Route::put('/queue/{id}', [AdminQueueController::class, 'updateEntry'])->name('api.queue.update');
                Route::delete('/queue/{id}', [AdminQueueController::class, 'remove'])->name('api.queue.delete');
                Route::post('/queue/{id}/serve', [AdminQueueController::class, 'serve'])->name('api.queue.serve');
                Route::post('/queue/{id}/complete', [AdminQueueController::class, 'complete'])->name('api.queue.complete');
                Route::post('/queue/{id}/return-waiting', [AdminQueueController::class, 'returnToWaiting'])->name('api.queue.return-waiting');
                Route::post('/queue/{id}/priority', [AdminQueueController::class, 'priority'])->name('api.queue.priority');
                Route::post('/customers', [AdminCustomerV2Controller::class, 'store'])->name('api.customers.store');
                Route::put('/customers/{id}', [AdminCustomerV2Controller::class, 'update'])->name('api.customers.update');
                Route::delete('/customers/{id}', [AdminCustomerV2Controller::class, 'destroy'])->name('api.customers.destroy');
                Route::get('/customers/{id}', [AdminCustomerV2Controller::class, 'show'])->name('api.customers.show');
                Route::post('/invoices/{id}/send', [InvoiceController::class, 'send'])->name('api.invoices.send');
            });
        });
    });
});
