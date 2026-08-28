<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Auth\TenantProvisioningController;
use App\Jobs\FinalizeTenantProvisioning;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    Jobs\SeedDatabase::class,
                    FinalizeTenantProvisioning::class,
                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(true),
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],
            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],
            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register() {}

    public function boot()
    {
        $this->bootEvents();
        $this->registerTenantLanguageRoute();
        $this->registerProvisioningRoutes();
        $this->mapRoutes();
        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }
                Event::listen($event, $listener);
            }
        }
    }

    protected function registerTenantLanguageRoute(): void
    {
        Route::middleware([
            'web',
            Middleware\InitializeTenancyByDomain::class,
            Middleware\PreventAccessFromCentralDomains::class,
        ])->get('/change-language/{lang}', function (string $lang) {
            $supported = config('localizer.supported_locales', ['en', 'ar']);
            if (! in_array($lang, $supported, true)) {
                abort(404);
            }

            $tenant = function_exists('tenant') ? tenant() : null;
            if (! $tenant) {
                abort(404);
            }

            $settings = $tenant->run(
                fn () => \App\Models\Setting::where('tenant_id', $tenant->id)->first()
            );

            $available = $settings?->available_languages;
            if (is_string($available)) {
                $available = json_decode($available, true);
            }

            if (is_array($available) && $available !== [] && ! in_array($lang, $available, true)) {
                return redirect()->back()->with('error', __('This language is not enabled for this tenant.'));
            }

            session()->put('locale', $lang);
            session()->save();
            return redirect()->back();
        })->name('tenant.change.language');
    }

    protected function registerProvisioningRoutes(): void
    {
        Route::middleware(['web', 'maintenance'])
            ->get('/signup/provisioning/{token}', [TenantProvisioningController::class, 'show'])
            ->name('signup.provisioning');

        Route::middleware(['web', 'maintenance'])
            ->get('/signup/provisioning/{token}/status', [TenantProvisioningController::class, 'status'])
            ->name('signup.provisioning.status');

        Route::middleware(['web', 'maintenance'])
            ->post('/signup/provisioning/{token}/resend-verification', [TenantProvisioningController::class, 'resendVerification'])
            ->name('signup.provisioning.resend')
            ->middleware('throttle:3,1');

        Route::middleware([
            'web',
            Middleware\InitializeTenancyByDomain::class,
            Middleware\PreventAccessFromCentralDomains::class,
        ])->get('/email/verify/{token}', [TenantProvisioningController::class, 'verifyEmail'])
            ->name('tenant.email.verify')
            ->middleware('throttle:10,1');

        Route::middleware([
            'web',
            Middleware\InitializeTenancyByDomain::class,
            Middleware\PreventAccessFromCentralDomains::class,
        ])->get('/__velora/provisioning/{token}', [TenantProvisioningController::class, 'handoff'])
            ->name('tenant.provisioning.handoff');
    }

    protected function mapRoutes()
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            Middleware\PreventAccessFromCentralDomains::class,
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}
