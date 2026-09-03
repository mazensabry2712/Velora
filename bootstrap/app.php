<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckSubscriptionLimits;
use App\Http\Middleware\DetectCountryAndLocale;
use App\Http\Middleware\EnsureSubscriptionIsValid;
use App\Http\Middleware\EnsureTokenBelongsToTenant;
use App\Http\Middleware\InitializeTenancyByToken;
use App\Http\Middleware\InjectVeloraBrandStyles;
use App\Http\Middleware\RedirectIfOnboardingIncomplete;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetTenantLocale;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use NielsNumbers\LaravelLocalizer\Middleware\RedirectLocale;
use NielsNumbers\LaravelLocalizer\Middleware\SetLocale;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

$app = new Application(basePath: dirname(__DIR__));

$app->useLangPath(base_path('lang'));

return (new ApplicationBuilder($app))
    ->withKernels()
    ->withEvents()
    ->withCommands()
    ->withProviders()
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel 12 application exception handling is registered here.
        // Keep the framework defaults unless a domain-specific renderer is required.
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/tenant.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->append(InjectVeloraBrandStyles::class);

        $middleware->web(remove: [SubstituteBindings::class]);
        $middleware->web(append: [
            SetLocale::class,
            RedirectLocale::class,
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'tenant' => InitializeTenancyByDomain::class,
            'tenant.token' => InitializeTenancyByToken::class,
            'tenant.token.bound' => EnsureTokenBelongsToTenant::class,
            'tenant.locale' => SetTenantLocale::class,
            'role' => CheckRole::class,
            'super.admin.auth' => CheckRole::class,
            'ability' => CheckAbilities::class,
            'subscription.limits' => CheckSubscriptionLimits::class,
            'subscription.valid' => EnsureSubscriptionIsValid::class,
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
    ->create();
