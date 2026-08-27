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
use App\Http\Middleware\InjectVeloraBrandStyles;
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
        $middleware->append(SecurityHeaders::class);

        // Inject the central Velora brand layer and language selector at runtime.
        // The middleware bypasses PHPUnit requests so Laravel view assertions keep
        // receiving the original View response object.
        $middleware->append(InjectVeloraBrandStyles::class);

        $middleware->alias([
            'tenant' => InitializeTenancyByDomain::class,
            'tenant.token' => InitializeTenancyByToken::class,
            'tenant.locale' => SetTenantLocale::class,
            'super.admin' => CheckSuperAdmin::class,
            'super.admin.auth' => SuperAdminAuth::class,
            'role' => CheckRole::class,
            'ability' => CheckTokenAbility::class,
            'subscription.limits' => CheckSubscriptionLimits::class,
            'subscription.valid' => EnsureSubscriptionIsValid::class,
            'throttle.api' => ThrottleRequests::class,
            'geo.detect' => DetectCountryAndLocale::class,
            'onboarding.redirect' => RedirectIfOnboardingIncomplete::class,
            'maintenance' => CheckMaintenanceMode::class,
        ]);

        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhooks/stripe',
            'webhooks/moyasar',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();