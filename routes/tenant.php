<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\Tenant\AppointmentController;
use App\Http\Middleware\SetTenantLocale;

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
    })->name('queue.status');

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
