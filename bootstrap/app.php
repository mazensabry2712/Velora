<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckSubscriptionLimits;
use App\Http\Middleware\CheckSuperAdmin;
use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\DetectCountryAndLocale;
use App\Http\Middleware\EnsureSubscriptionIsValid;
use App\Http\Middleware\InitializeTenancyByToken;
use App\Http\Middleware\RedirectIfOnboardingIncomplete;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetTenantLocale;
use App\Http\Middleware\SuperAdminAuth;
use App\Http\Middleware\ThrottleRequests;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - security headers for all requests
        $middleware->append(SecurityHeaders::class);

        // Register tenancy middleware
        $middleware->alias([
            'tenant' => InitializeTenancyByDomain::class,
            'tenant.token' => InitializeTenancyByToken::class,
            'tenant.locale' => SetTenantLocale::class,
            'super.admin' => CheckSuperAdmin::class,
            'super.admin.auth' => SuperAdminAuth::class,

            // Role-based middleware
            'role' => CheckRole::class,
            'ability' => CheckTokenAbility::class,

            // Subscription limits middleware
            'subscription.limits' => CheckSubscriptionLimits::class,

            // Subscription validity (trial/grace/expired)
            'subscription.valid' => EnsureSubscriptionIsValid::class,

            // Rate limiting
            'throttle.api' => ThrottleRequests::class,

            // Geo localization (country detection + locale + currency)
            'geo.detect' => DetectCountryAndLocale::class,

            // Onboarding wizard redirect (first-login flow)
            'onboarding.redirect' => RedirectIfOnboardingIncomplete::class,

            // Maintenance mode for landing/public pages
            'maintenance' => CheckMaintenanceMode::class,
        ]);

        // Enable session and cookies for API routes (needed for Super Admin web-based auth)
        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ]);

        // Exclude API routes and payment webhooks from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhooks/stripe',
            'webhooks/moyasar',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();



    // ,,