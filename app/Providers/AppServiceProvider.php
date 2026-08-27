<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Shared\Contracts\TransactionManager;
use App\Application\Subscription\Events\SubscriptionUpgradeRequested;
use App\Domain\Pricing\Contracts\CountryPriceSelector;
use App\Domain\Queue\Contracts\QueueRepository as DomainQueueRepository;
use App\Domain\Reporting\Contracts\ReportReader;
use App\Domain\Shared\Contracts\PaymentGatewayResolver;
use App\Domain\Subscription\Contracts\SubscriptionReader;
use App\Domain\Subscription\Contracts\UpgradeRequestWriter;
use App\Domain\Tenant\Contracts\TenantRegistrar;
use App\Infrastructure\Billing\EloquentUpgradeRequestWriter;
use App\Infrastructure\Billing\LegacySubscriptionReader;
use App\Infrastructure\Persistence\LaravelTransactionManager;
use App\Infrastructure\Pricing\LegacyCountryPriceSelector;
use App\Infrastructure\Reporting\LegacyReportReader;
use App\Infrastructure\Subscription\Listeners\SendUpgradeRequestNotifications;
use App\Infrastructure\Tenancy\LegacyTenantRegistrar;
use App\Models\Appointment;
use App\Models\SystemSetting;
use App\Observers\AppointmentObserver;
use App\Payments\PaymentGatewayManager;
use App\Repositories\Eloquent\QueueRepository;
use App\Services\PaymentGatewayRouter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(PaymentGatewayResolver::class, PaymentGatewayRouter::class);
        $this->app->bind(SubscriptionReader::class, LegacySubscriptionReader::class);
        $this->app->bind(UpgradeRequestWriter::class, EloquentUpgradeRequestWriter::class);
        $this->app->bind(DomainQueueRepository::class, QueueRepository::class);
        $this->app->bind(ReportReader::class, LegacyReportReader::class);
        $this->app->bind(CountryPriceSelector::class, LegacyCountryPriceSelector::class);
        $this->app->bind(TenantRegistrar::class, LegacyTenantRegistrar::class);
    }

    public function boot(): void
    {
        Event::listen(
            SubscriptionUpgradeRequested::class,
            SendUpgradeRequestNotifications::class,
        );

        Appointment::observe(AppointmentObserver::class);

        View::composer('layouts.landing', function ($view) {
            try {
                $appName = SystemSetting::get('app_name', config('app.name', 'Velora'));
                $appLogoUrl = SystemSetting::get('app_logo_url', '');
                $registrationEnabled = SystemSetting::get('registration_enabled', true);
                $defaultTrialDays = SystemSetting::get('default_trial_days', 14);
            } catch (\Throwable $e) {
                $appName = config('app.name', 'Velora');
                $appLogoUrl = '';
                $registrationEnabled = true;
                $defaultTrialDays = 14;
            }

            $view->with(compact('appName', 'appLogoUrl', 'registrationEnabled', 'defaultTrialDays'));
        });

        View::composer('layouts.admin', function ($view) {
            try {
                $tenantId = tenant('id');
                if (!$tenantId) {
                    return;
                }

                $notifications = DB::connection('mysql')
                    ->table('system_notifications')
                    ->where('is_sent', true)
                    ->where(function ($q) use ($tenantId) {
                        $q->where('target', 'all')
                          ->orWhere(function ($q2) use ($tenantId) {
                              $q2->where('target', 'specific')
                                 ->whereJsonContains('tenant_ids', $tenantId);
                          });
                    })
                    ->where('sent_at', '>=', now()->subDays(7))
                    ->orderByDesc('sent_at')
                    ->limit(5)
                    ->get(['id', 'title', 'message', 'type', 'sent_at']);

                $view->with('systemNotifications', $notifications);
            } catch (\Throwable $e) {
                $view->with('systemNotifications', collect());
            }
        });
    }
}
