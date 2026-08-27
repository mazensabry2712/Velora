<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscription\Listeners;

use App\Application\Subscription\Events\SubscriptionUpgradeRequested;
use App\Mail\FounderAlertMail;
use App\Mail\UpgradeRequestedMail;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendUpgradeRequestNotifications implements ShouldQueue
{
    public function handle(SubscriptionUpgradeRequested $event): void
    {
        ActivityLog::log(
            'upgrade_requested',
            "Tenant requested upgrade from plan [{$event->currentPlanName}] to [{$event->requestedPlanName}]. Requested by: {$event->requesterEmail}"
        );

        try {
            Mail::to($event->requesterEmail)->queue(new UpgradeRequestedMail(
                tenantName: $event->requesterName,
                currentPlanName: $event->currentPlanName,
                requestedPlanName: $event->requestedPlanName,
                requestedPlanPrice: $event->requestedPlanPrice,
            ));
        } catch (\Throwable $exception) {
            Log::warning('Failed to queue tenant upgrade confirmation email.', [
                'tenant_id' => $event->tenantId,
                'exception' => $exception->getMessage(),
            ]);
        }

        try {
            $adminEmail = config('mail.founder_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new FounderAlertMail(
                    tenantId: $event->tenantId,
                    businessName: $event->currentPlanName ?: $event->tenantId,
                    ownerEmail: $event->requesterEmail,
                    triggerReason: 'New upgrade request: ' . $event->currentPlanName . ' → ' . $event->requestedPlanName,
                    trialDaysLeft: 0,
                ));
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to queue founder upgrade alert.', [
                'tenant_id' => $event->tenantId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
