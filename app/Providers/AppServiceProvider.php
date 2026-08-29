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
use App\Domain\Landing\Contracts\LandingSettingsReader;
use App\Domain\Notifications\Contracts\WhatsAppProvider;
use App\Domain\Pricing\CountryPriceSelector;
use App\Domain\Queue\Contracts\QueueReader;
use App\Domain\Queue\Contracts\QueueRepository as DomainQueueRepository;
use App\Domain\Queue\Events\QueueLifecycleNotificationRequested;
use App\Domain\Reporting\Contracts\ReportReader;
use App\Domain\Shared\Contracts\PaymentGatewayResolver;
use App\Domain\Staff\Contracts\StaffWriter;
use App\Domain\Subscription\Contracts\SubscriptionAccessReader;
use App\Domain\Subscription\Contracts\SubscriptionReader;
use App\Domain\Subscription\Contracts\UpgradeRequestWriter;
use App\Domain\Tenant\Contracts\TenantRegistrar;
use App\Infrastructure\Administration\LegacySystemNotificationReader;
use App\Infrastructure\Billing\EloquentBillingReader;
use App\Infrastructure\Billing\EloquentSubscriptionReader;
use App\Infrastructure\Billing\EloquentTrialExtender;
use App\Infrastructure\Billing\EloquentUpgradeRequestWriter;
use App\Infrastructure\Billing\PaymentGatewayCheckoutSessionCreator;
use App\Infrastructure\Booking\EloquentAppointmentReader;
use App\Infrastructure\Customer\EloquentCustomerReader;
use App\Infrastructure\Landing\LegacyLandingSettingsReader;
use App\Infrastructure\Notifications\Listeners\CreateQueueLifecycleNotificationDeliveries;
use App\Infrastructure\Notifications\NullWhatsAppProvider;
use App\Infrastructure\Payments\Moyasar\MoyasarWebhookProcessor;
use App\Infrastructure\Payments\PaymentGatewayRouter;
use App\Infrastructure\Payments\Stripe\StripeWebhookProcessor as StripeWebhookProcessorImplementation;
use App\Infrastructure\Persistence\LaravelTransactionManager;
use App\Infrastructure\Pricing\LegacyCountryPriceSelector;
use App\Infrastructure\Queue\EloquentQueueReader;
use App\Infrastructure\Reporting\LegacyReportReader;
use App\Infrastructure\Staff\EloquentStaffWriter;
use App\Infrastructure\Subscription\EloquentSubscriptionAccessReader;
use App\Infrastructure\Subscription\Listeners\SendUpgradeRequestNotifications;
use App\Infrastructure\Tenancy\LegacyTenantRegistrar;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;
use App\Payments\PaymentGatewayManager;
use App\Repositories\Eloquent\QueueRepository;
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
        $this->app->bind(BillingReader::class, EloquentBillingReader::class);
        $this->app->bind(CheckoutSessionCreator::class, PaymentGatewayCheckoutSessionCreator::class);
        $this->app->bind(TrialExtender::class, EloquentTrialExtender::class);
        $this->app->bind(CustomerReader::class, EloquentCustomerReader::class);
        $this->app->bind(StaffWriter::class, EloquentStaffWriter::class);
        $this->app->bind(QueueReader::class, EloquentQueueReader::class);
        $this->app->bind(DomainQueueRepository::class, QueueRepository::class);
        $this->app->bind(AppointmentReader::class, EloquentAppointmentReader::class);
        $this->app->bind(CountryPriceSelector::class, LegacyCountryPriceSelector::class);
        $this->app->bind(LandingSettingsReader::class, LegacyLandingSettingsReader::class);
        $this->app->bind(ReportReader::class, LegacyReportReader::class);
        $this->app->bind(TenantRegistrar::class, LegacyTenantRegistrar::class);
        $this->app->bind(SubscriptionReader::class, EloquentSubscriptionReader::class);
        $this->app->bind(SubscriptionAccessReader::class, EloquentSubscriptionAccessReader::class);
        $this->app->bind(UpgradeRequestWriter::class, EloquentUpgradeRequestWriter::class);
        $this->app->bind(MoyasarWebhookProcessorContract::class, MoyasarWebhookProcessor::class);
        $this->app->bind(StripeWebhookProcessor::class, StripeWebhookProcessorImplementation::class);
        $this->app->bind(PaymentGatewayResolver::class, PaymentGatewayRouter::class);
        $this->app->bind(WhatsAppProvider::class, NullWhatsAppProvider::class);
        $this->app->singleton(PaymentGatewayManager::class);
    }

    public function boot(): void
    {
        Event::listen(
            SubscriptionUpgradeRequested::class,
            SendUpgradeRequestNotifications::class,
        );

        Event::listen(
            QueueLifecycleNotificationRequested::class,
            CreateQueueLifecycleNotificationDeliveries::class,
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
