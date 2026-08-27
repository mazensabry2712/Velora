<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Shared\Contracts\TransactionManager;
use App\Application\Subscription\Events\SubscriptionUpgradeRequested;
use App\Domain\Administration\Contracts\SystemNotificationReader;
use App\Domain\Billing\Contracts\BillingReader;
use App\Domain\Billing\Contracts\CheckoutSessionCreator;
use App\Domain\Billing\Contracts\MoyasarWebhookProcessor as MoyasarWebhookProcessorContract;
use App\Domain\Billing\Contracts\StripeWebhookProcessor;
use App\Domain\Billing\Contracts\TrialExtender;
use App\Domain\Booking\Contracts\AppointmentReader;
use App\Domain\Customer\Contracts\CustomerReader;
use App\Domain\Customer\Contracts\CustomerStatisticsReader;
use App\Domain\Landing\Contracts\LandingSettingsReader;
use App\Domain\Pricing\Contracts\CountryPriceSelector;
use App\Domain\Queue\Contracts\QueueReader;
use App\Domain\Queue\Contracts\QueueRepository as DomainQueueRepository;
use App\Domain\Reporting\Contracts\ReportReader;
use App\Domain\Shared\Contracts\PaymentGatewayResolver;
use App\Domain\Staff\Contracts\StaffWriter;
use App\Domain\Subscription\Contracts\SubscriptionAccessReader;
use App\Domain\Subscription\Contracts\SubscriptionReader;
use App\Domain\Subscription\Contracts\UpgradeRequestWriter;
use App\Domain\Tenant\Contracts\TenantRegistrar;
use App\Infrastructure\Administration\LegacySystemNotificationReader;
use App\Infrastructure\Billing\EloquentUpgradeRequestWriter;
use App\Infrastructure\Billing\LegacyBillingReader;
use App\Infrastructure\Billing\LegacySubscriptionAccessReader;
use App\Infrastructure\Billing\LegacySubscriptionReader;
use App\Infrastructure\Billing\LegacyTrialExtender;
use App\Infrastructure\Billing\PaymentGatewayCheckoutSessionCreator;
use App\Infrastructure\Booking\EloquentAppointmentReader;
use App\Infrastructure\Customer\EloquentCustomerReader;
use App\Infrastructure\Customer\EloquentCustomerStatisticsReader;
use App\Infrastructure\Landing\LegacyLandingSettingsReader;
use App\Infrastructure\Payments\Moyasar\MoyasarWebhookProcessor;
use App\Infrastructure\Payments\Stripe\StripeWebhookProcessor as StripeWebhookProcessorImplementation;
use App\Infrastructure\Persistence\LaravelTransactionManager;
use App\Infrastructure\Pricing\LegacyCountryPriceSelector;
use App\Infrastructure\Queue\EloquentQueueReader;
use App\Infrastructure\Reporting\LegacyReportReader;
use App\Infrastructure\Staff\EloquentStaffWriter;
use App\Infrastructure\Subscription\Listeners\SendUpgradeRequestNotifications;
use App\Infrastructure\Tenancy\LegacyTenantRegistrar;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;
use App\Payments\PaymentGatewayManager;
use App\Repositories\Eloquent\QueueRepository;
use App\Services\PaymentGatewayRouter;
use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\LandingLayoutComposer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(SystemNotificationReader::class, LegacySystemNotificationReader::class);
        $this->app->bind(BillingReader::class, LegacyBillingReader::class);
        $this->app->bind(CheckoutSessionCreator::class, PaymentGatewayCheckoutSessionCreator::class);
        $this->app->bind(TrialExtender::class, LegacyTrialExtender::class);
        $this->app->bind(CustomerReader::class, EloquentCustomerReader::class);
        $this->app->bind(CustomerStatisticsReader::class, EloquentCustomerStatisticsReader::class);
        $this->app->bind(StaffWriter::class, EloquentStaffWriter::class);
        $this->app->bind(QueueReader::class, EloquentQueueReader::class);
        $this->app->bind(DomainQueueRepository::class, QueueRepository::class);
        $this->app->bind(AppointmentReader::class, EloquentAppointmentReader::class);
        $this->app->bind(CountryPriceSelector::class, LegacyCountryPriceSelector::class);
        $this->app->bind(LandingSettingsReader::class, LegacyLandingSettingsReader::class);
        $this->app->bind(ReportReader::class, LegacyReportReader::class);
        $this->app->bind(TenantRegistrar::class, LegacyTenantRegistrar::class);
        $this->app->bind(SubscriptionReader::class, LegacySubscriptionReader::class);
        $this->app->bind(SubscriptionAccessReader::class, LegacySubscriptionAccessReader::class);
        $this->app->bind(UpgradeRequestWriter::class, EloquentUpgradeRequestWriter::class);
        $this->app->bind(MoyasarWebhookProcessorContract::class, MoyasarWebhookProcessor::class);
        $this->app->bind(StripeWebhookProcessor::class, StripeWebhookProcessorImplementation::class);
        $this->app->bind(PaymentGatewayResolver::class, PaymentGatewayRouter::class);
    }

    public function boot(): void
    {
        Event::listen(
            SubscriptionUpgradeRequested::class,
            SendUpgradeRequestNotifications::class,
        );

        Appointment::observe(AppointmentObserver::class);

        ViewFacade::composer('layouts.landing', LandingLayoutComposer::class);
        ViewFacade::composer('layouts.admin', AdminLayoutComposer::class);

        ViewFacade::composer('admin.appointments.index', function (View $view): void {
            $data = $view->getData();

            if (! array_key_exists('appointments', $data) && array_key_exists('paginatedData', $data)) {
                $view->with('appointments', $data['paginatedData']);
            }
        });
    }
}
