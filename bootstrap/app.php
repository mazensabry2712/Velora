<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckSubscriptionLimits;
use App\Http\Middleware\CheckSuperAdmin;
use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\DetectCountryAndLocale;
use App\Http\Middleware\EnforceCentralLocale;
use App\Http\Middleware\EnsurePublicAuthCopyTranslations;
use App\Http\Middleware\EnsurePublicFrenchTranslations;
use App\Http\Middleware\EnsurePublicLoginCopyTranslations;
use App\Http\Middleware\EnsureSubscriptionIsValid;
use App\Http\Middleware\EnsureTokenBelongsToTenant;
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
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            EnforceCentralLocale::class,
            EnsurePublicFrenchTranslations::class,
            EnsurePublicAuthCopyTranslations::class,
            EnsurePublicLoginCopyTranslations::class,
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'tenant' => InitializeTenancyByDomain::class,
            'tenant.token' => InitializeTenancyByToken::class,
            'tenant.token.bound' => EnsureTokenBelongsToTenant::class,
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
        $exceptions->render(function (ThrottleRequestsException $exception, $request) {
            if ($request->is('api/appointments')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Too many booking attempts. Please try again later.',
                ], 429);
            }

            return null;
        });
    })
    ->create();
