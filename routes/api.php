<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\Auth\TenantAuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Super Admin Authentication (Central)
Route::prefix('api/super-admin/auth')->group(function () {
    Route::post('/login', [SuperAdminAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [SuperAdminAuthController::class, 'profile']);
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
    });
});

// Tenant Authentication (By Domain)
Route::middleware(['tenant', 'tenant.locale'])->prefix('auth')->group(function () {
    Route::post('/login', [TenantAuthController::class, 'login']);
    Route::post('/register', [TenantAuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [TenantAuthController::class, 'profile']);
        Route::post('/logout', [TenantAuthController::class, 'logout']);
    });
});

// Tenant Authentication (By Token)
Route::prefix('api/v1/auth')->middleware(['tenant.token', 'tenant.locale'])->group(function () {
    Route::post('/login', [TenantAuthController::class, 'login']);
    Route::post('/register', [TenantAuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [TenantAuthController::class, 'profile']);
        Route::post('/logout', [TenantAuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| API Routes - Super Admin
|--------------------------------------------------------------------------
| Routes for Super Admin to manage all tenants
*/

Route::prefix('api/super-admin')->middleware(['auth:sanctum', 'super.admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/tenants-overview', [DashboardController::class, 'tenantsOverview']);
    Route::get('/dashboard/system-stats', [DashboardController::class, 'systemStats']);

    // Tenants Management
    Route::apiResource('tenants', TenantController::class);
    Route::post('/tenants/{id}/toggle-status', [TenantController::class, 'toggleStatus']);
    Route::get('/tenants/{id}/statistics', [TenantController::class, 'statistics']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Tenant (By Domain/Subdomain)
|--------------------------------------------------------------------------
| Routes for tenant users accessed via subdomain
*/

// Public API Routes (No auth required) - For booking form
Route::prefix('api')->middleware(['tenant', 'tenant.locale'])->group(function () {
    // Get staff list for booking form
    Route::get('staff', function () {
        return \App\Models\User::role('Staff')->select('id', 'name')->get();
    });

    // Create appointment (public)
    Route::post('appointments', [\App\Http\Controllers\Tenant\AppointmentController::class, 'store']);
});

Route::prefix('api')->middleware(['tenant', 'tenant.locale', 'auth:sanctum'])->group(function () {

    // Appointments
    // Admin Tenant & Staff can manage all appointments
    Route::middleware(['role:Admin Tenant|Staff'])->group(function () {
        Route::apiResource('appointments', \App\Http\Controllers\Tenant\AppointmentController::class)->except(['store']);
    });

    // Customers can manage their own appointments
    Route::middleware(['role:Customer'])->group(function () {
        Route::get('my-appointments', [\App\Http\Controllers\Tenant\AppointmentController::class, 'myAppointments']);
    });

    // Public Queue Status Check (no auth required)
    Route::get('queue/status/{queueNumber}', [\App\Http\Controllers\Tenant\QueueController::class, 'getQueueStatus']);

    // Queues - Admin & Staff
    Route::middleware(['role:Admin Tenant|Staff'])->group(function () {
        // Queue Management Routes (المطلوب في المرحلة 6)
        Route::get('queue', [\App\Http\Controllers\Tenant\QueueController::class, 'index']);
        Route::post('queue/add', [\App\Http\Controllers\Tenant\QueueController::class, 'add']);
        Route::post('queue/next', [\App\Http\Controllers\Tenant\QueueController::class, 'next']);
        Route::post('queue/priority', [\App\Http\Controllers\Tenant\QueueController::class, 'priority']);

        // Additional queue routes
        Route::apiResource('queues', \App\Http\Controllers\Tenant\QueueController::class);
        Route::get('queues/status/{status}', [\App\Http\Controllers\Tenant\QueueController::class, 'byStatus']);
        Route::post('quequeues/{id}/skip', [\App\Http\Controllers\Tenant\QueueController::class, 'skip']);
    });

    // Queues - Customer
    Route::middleware(['role:Customer'])->group(function () {
        Route::get('my-queue', [\App\Http\Controllers\Tenant\QueueController::class, 'myQueue']);
    });

    // Notifications (All authenticated users)
    Route::get('notifications', [\App\Http\Controllers\Tenant\NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [\App\Http\Controllers\Tenant\NotificationController::class, 'unreadCount']);
    Route::get('notifications/{id}', [\App\Http\Controllers\Tenant\NotificationController::class, 'show']);
    Route::post('notifications/{id}/read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [\App\Http\Controllers\Tenant\NotificationController::class, 'destroy']);

    // Invoices
    Route::middleware(['role:Admin Tenant'])->group(function () {
        Route::apiResource('invoices', \App\Http\Controllers\Tenant\InvoiceController::class);
    });

    Route::middleware(['role:Customer'])->group(function () {
        Route::get('my-invoices', [\App\Http\Controllers\Tenant\InvoiceController::class, 'myInvoices']);
        Route::get('invoices/{id}/download', [\App\Http\Controllers\Tenant\InvoiceController::class, 'download']);
    });

    // Settings (Admin only)
    Route::middleware(['role:Admin Tenant'])->group(function () {
        Route::get('settings', [\App\Http\Controllers\Tenant\SettingController::class, 'show']);
        Route::put('settings', [\App\Http\Controllers\Tenant\SettingController::class, 'update']);
    });

    // Reports & Dashboard (Admin Tenant only)
    Route::middleware(['role:Admin Tenant'])->group(function () {
        Route::get('reports/dashboard', [\App\Http\Controllers\Tenant\ReportController::class, 'dashboard']);
        Route::get('reports/appointments/export-pdf', [\App\Http\Controllers\Tenant\ReportController::class, 'exportAppointmentsPDF']);
        Route::get('reports/appointments/export-csv', [\App\Http\Controllers\Tenant\ReportController::class, 'exportAppointmentsCSV']);
        Route::get('reports/invoices/export-csv', [\App\Http\Controllers\Tenant\ReportController::class, 'exportInvoicesCSV']);
        Route::get('reports/invoice/{id}/pdf', [\App\Http\Controllers\Tenant\ReportController::class, 'exportInvoicePDF']);
    });
});

/*
|--------------------------------------------------------------------------
| API Routes - Tenant (By Token/Header)
|--------------------------------------------------------------------------
| Routes for API access using tenant token in header
*/

Route::prefix('api/v1')->middleware(['tenant.token', 'tenant.locale', 'auth:sanctum'])->group(function () {

    // Appointments
    Route::apiResource('appointments', \App\Http\Controllers\Tenant\AppointmentController::class);

    // Queues
    Route::apiResource('queues', \App\Http\Controllers\Tenant\QueueController::class);

    // Notifications
    Route::apiResource('notifications', \App\Http\Controllers\Tenant\NotificationController::class);

    // Invoices
    Route::apiResource('invoices', \App\Http\Controllers\Tenant\InvoiceController::class);
});
