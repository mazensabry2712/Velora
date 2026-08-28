<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\SystemSettingController;
use App\Http\Controllers\SuperAdmin\SystemNotificationController;
use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\Auth\TenantAuthController;
use App\Http\Controllers\Tenant\AdvanceQueueController;
use App\Http\Controllers\Tenant\AdminAppointmentCreationController;
use App\Http\Controllers\Tenant\QueueMutationController;
use App\Http\Controllers\Tenant\QueueReadController;

/* Authentication */
Route::prefix('super-admin/auth')->group(function () {
    Route::post('/login', [SuperAdminAuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [SuperAdminAuthController::class, 'profile']);
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
    });
});

Route::middleware(['tenant', 'tenant.locale'])->prefix('auth')->group(function () {
    Route::post('/login', [TenantAuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('/register', [TenantAuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [TenantAuthController::class, 'profile']);
        Route::post('/logout', [TenantAuthController::class, 'logout']);
    });
});

Route::prefix('v1/auth')->middleware(['tenant.token', 'tenant.locale'])->group(function () {
    Route::post('/login', [TenantAuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('/register', [TenantAuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::middleware(['auth:sanctum', 'tenant.token.bound'])->group(function () {
        Route::get('/profile', [TenantAuthController::class, 'profile']);
        Route::post('/logout', [TenantAuthController::class, 'logout']);
    });
});

/* Super Admin */
Route::prefix('super-admin')->middleware(['auth:web', 'super.admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/tenants-overview', [DashboardController::class, 'tenantsOverview']);
    Route::get('/dashboard/system-stats', [DashboardController::class, 'systemStats']);
    Route::get('/dashboard/subscription-stats', [DashboardController::class, 'subscriptionStats']);
    Route::get('/dashboard/activity-summary', [DashboardController::class, 'activitySummary']);
    Route::get('/dashboard/growth-metrics', [DashboardController::class, 'growthMetrics']);
    Route::get('/dashboard/revenue-metrics', [DashboardController::class, 'revenueMetrics']);
    Route::get('/tenants/trash', [TenantController::class, 'trash']);
    Route::get('/tenants/export-excel', [TenantController::class, 'exportExcel']);
    Route::post('/tenants/restore-all', [TenantController::class, 'restoreAll']);
    Route::delete('/tenants/delete-all', [TenantController::class, 'deleteAll']);
    Route::post('/tenants/{id}/restore', [TenantController::class, 'restore']);
    Route::delete('/tenants/{id}/force-delete', [TenantController::class, 'forceDelete']);
    Route::delete('/tenants/force-delete-all', [TenantController::class, 'forceDeleteAll']);
    Route::apiResource('tenants', TenantController::class);
    Route::post('/tenants/{id}/toggle-status', [TenantController::class, 'toggleStatus']);
    Route::get('/tenants/{id}/statistics', [TenantController::class, 'statistics']);
    Route::post('/tenants/{id}/assign-subscription', [TenantController::class, 'assignSubscription']);
    Route::get('/tenants/{id}/users', [TenantController::class, 'users']);
    Route::post('/tenants/{id}/reset-admin-password', [TenantController::class, 'resetAdminPassword']);
    Route::get('/tenants/{id}/subscription', [TenantController::class, 'subscription']);
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);
    Route::post('/subscription-plans/{id}/toggle-status', [SubscriptionPlanController::class, 'toggleStatus']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/statistics', [ActivityLogController::class, 'statistics']);
    Route::post('/activity-logs/clear-old', [ActivityLogController::class, 'clearOld']);
    Route::get('/settings', [SystemSettingController::class, 'index']);
    Route::get('/settings/{key}', [SystemSettingController::class, 'show']);
    Route::post('/settings', [SystemSettingController::class, 'store']);
    Route::put('/settings', [SystemSettingController::class, 'update']);
    Route::delete('/settings/{key}', [SystemSettingController::class, 'destroy']);
    Route::apiResource('notifications', SystemNotificationController::class)->except(['update']);
    Route::post('/notifications/{id}/send', [SystemNotificationController::class, 'send']);
});

/* Versioned tenant API (V1) */
Route::prefix('v1')
    ->middleware(['tenant.token', 'tenant.locale'])
    ->group(function () {
        Route::middleware(['auth:sanctum', 'tenant.token.bound'])->group(function () {
            Route::middleware(['role:Admin Tenant|Staff'])->group(function () {
                Route::get('appointments', [\App\Http\Controllers\Tenant\AppointmentController::class, 'index']);
                Route::post('appointments', [AdminAppointmentCreationController::class, 'store']);
                Route::get('queues', [\App\Http\Controllers\Tenant\QueueController::class, 'index']);
            });

            Route::middleware(['role:Admin Tenant'])->group(function () {
                Route::apiResource('invoices', \App\Http\Controllers\Tenant\InvoiceController::class);
                Route::get('analytics/summary', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'summary']);
                Route::get('analytics/daily', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'daily']);
            });

            Route::prefix('push-tokens')->group(function () {
                Route::get('/', [\App\Http\Controllers\Tenant\PushTokenController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Tenant\PushTokenController::class, 'store']);
                Route::delete('/{id}', [\App\Http\Controllers\Tenant\PushTokenController::class, 'destroy']);
            });
        });
    });

/* Tenant APIs by domain */
Route::middleware(['tenant', 'tenant.locale'])->group(function () {
    Route::get('staff', function () {
        return \App\Models\User::role('Staff')->select('id', 'name')->get();
    })->middleware('throttle:60,1');
    Route::post('appointments', [\App\Http\Controllers\Tenant\PublicBookingController::class, 'store'])
        ->middleware('throttle.api:public-booking,5,1');
    Route::get('queue/status/{queueNumber}', [QueueReadController::class, 'status'])
        ->middleware('throttle:60,1');
});

Route::middleware(['tenant', 'tenant.locale', 'auth:sanctum'])->group(function () {
    Route::middleware(['role:Admin Tenant|Staff'])->group(function () {
        Route::apiResource('appointments', \App\Http\Controllers\Tenant\AppointmentController::class)->except(['store'])->names([
            'index' => 'api.appointments.index', 'show' => 'api.appointments.show', 'update' => 'api.appointments.update', 'destroy' => 'api.appointments.destroy',
        ]);
        Route::get('queue', [QueueReadController::class, 'index']);
        Route::post('queue/add', [QueueMutationController::class, 'add']);
        Route::post('queue/next', AdvanceQueueController::class);
        Route::post('queue/priority', [\App\Http\Controllers\Tenant\QueueController::class, 'priority']);
        Route::apiResource('queues', \App\Http\Controllers\Tenant\QueueController::class);
        Route::get('queues/status/{status}', [QueueReadController::class, 'byStatus']);
        Route::post('queues/{id}/skip', [QueueMutationController::class, 'skip']);
    });

    Route::middleware(['role:Customer'])->group(function () {
        Route::get('my-appointments', [\App\Http\Controllers\Tenant\AppointmentController::class, 'myAppointments']);
        Route::get('my-queue', [QueueReadController::class, 'myQueue']);
        Route::get('my-invoices', [\App\Http\Controllers\Tenant\InvoiceController::class, 'myInvoices']);
        Route::get('invoices/{id}/download', [\App\Http\Controllers\Tenant\InvoiceController::class, 'download']);
    });

    Route::get('notifications', [\App\Http\Controllers\Tenant\NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [\App\Http\Controllers\Tenant\NotificationController::class, 'unreadCount']);
    Route::get('notifications/{id}', [\App\Http\Controllers\Tenant\NotificationController::class, 'show']);
    Route::post('notifications/{id}/read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [\App\Http\Controllers\Tenant\NotificationController::class, 'destroy']);

    Route::middleware(['role:Admin Tenant'])->group(function () {
        Route::apiResource('invoices', \App\Http\Controllers\Tenant\InvoiceController::class);
        Route::get('settings', [\App\Http\Controllers\Tenant\SettingController::class, 'show']);
        Route::put('settings', [\App\Http\Controllers\Tenant\SettingController::class, 'update']);
        Route::get('reports/dashboard', [\App\Http\Controllers\Tenant\ReportController::class, 'dashboard']);
        Route::get('reports/appointments/export-pdf', [\App\Http\Controllers\Tenant\ReportController::class, 'exportAppointmentsPDF']);
    });
});
