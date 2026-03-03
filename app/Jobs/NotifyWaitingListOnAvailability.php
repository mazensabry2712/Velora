<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\WaitingList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * NotifyWaitingListOnAvailability
 *
 * Dispatched automatically when an appointment is cancelled.
 * Finds the top waiting-list entry that matches the freed slot
 * (same service, same date, and optionally same staff), and
 * sends them an e-mail notification.
 *
 * Dispatched by: AppointmentObserver::updated()
 */
class NotifyWaitingListOnAvailability implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries  = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly int   $serviceId,
        private readonly ?int  $staffId,
        private readonly ?string $preferredDate,
    ) {}

    public function handle(): void
    {
        // Locate the first waiting entry that matches the freed slot
        $query = WaitingList::with(['customer', 'service'])
            ->where('service_id', $this->serviceId)
            ->where('status', 'waiting')
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->orderBy('created_at');  // FIFO

        // If cancelled appointment had a specific date, prefer matching preferred_date
        if ($this->preferredDate) {
            // First try exact date match, fall back to null preferred_date
            /** @var WaitingList|null $entry */
            $entry = $query->clone()
                ->where(function ($q) {
                    $q->whereDate('preferred_date', $this->preferredDate)
                      ->orWhereNull('preferred_date');
                })
                ->first();
        } else {
            $entry = $query->first();
        }

        if (! $entry) {
            return; // Nobody waiting for this slot
        }

        $customer = $entry->customer;

        if (! $customer || ! $customer->email) {
            Log::info("WaitingList Job: entry #{$entry->id} has no customer email — skipping");
            return;
        }

        try {
            $serviceName = $entry->service?->name ?? __('your requested service');

            Mail::raw(
                __("Good news! A slot has become available for :service. Please book now at your earliest convenience before it's taken.", [
                    'service' => $serviceName,
                ]),
                function ($message) use ($customer, $serviceName) {
                    $message->to($customer->email, $customer->first_name . ' ' . $customer->last_name)
                            ->subject(__('A slot opened up for :service!', ['service' => $serviceName]));
                }
            );

            $entry->update([
                'status'             => 'notified',
                'notified_at'        => now(),
                'notification_count' => $entry->notification_count + 1,
            ]);

            Log::info("WaitingList Job: notified customer #{$customer->id} (entry #{$entry->id}) for service #{$this->serviceId}");
        } catch (\Throwable $e) {
            Log::error("WaitingList Job: failed to notify entry #{$entry->id}: " . $e->getMessage());
            throw $e; // allow retry
        }
    }
}
