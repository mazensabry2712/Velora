<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load tenant routes
            Route::middleware([
                'web',
                \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/tenant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - security headers for all requests
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Register tenancy middleware
        $middleware->alias([
            'tenant' => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            'tenant.token' => \App\Http\Middleware\InitializeTenancyByToken::class,
            'tenant.locale' => \App\Http\Middleware\SetTenantLocale::class,
            'super.admin' => \App\Http\Middleware\CheckSuperAdmin::class,
            'super.admin.auth' => \App\Http\Middleware\SuperAdminAuth::class,

            // Role-based middleware
            'role' => \App\Http\Middleware\CheckRole::class,
            'ability' => \App\Http\Middleware\CheckTokenAbility::class,

            // Subscription limits middleware
            'subscription.limits' => \App\Http\Middleware\CheckSubscriptionLimits::class,

            // Subscription validity (trial/grace/expired)
            'subscription.valid' => \App\Http\Middleware\EnsureSubscriptionIsValid::class,

            // Rate limiting
            'throttle.api' => \App\Http\Middleware\ThrottleRequests::class,
        ]);

        // Enable session and cookies for API routes (needed for Super Admin web-based auth)
        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        // Exclude API routes and Stripe webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhooks/stripe',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
