<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Queue\Actions\AdvanceQueue;
use App\Jobs\SendQueueNotification;
use Illuminate\Http\JsonResponse;
use Throwable;

final class AdvanceQueueController
{
    public function __construct(
        private readonly AdvanceQueue $advanceQueue,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $nextQueue = $this->advanceQueue->execute();

            if (! $nextQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No waiting customers in queue',
                ], 404);
            }

            try {
                $customer = $nextQueue->appointment?->customer;
                $tenant = tenant();
                $locale = $tenant->settings->language ?? 'en';

                if ($customer) {
                    SendQueueNotification::dispatch($nextQueue, $customer, 'next', $locale);
                }

                $readyQueue = $nextQueue->newQuery()
                    ->where('status', 'waiting')
                    ->whereDate('created_at', now()->toDateString())
                    ->orderByDesc('is_vip')
                    ->orderBy('queue_number')
                    ->first();

                if ($readyQueue) {
                    $readyCustomer = $readyQueue->appointment?->customer;
                    if ($readyCustomer) {
                        SendQueueNotification::dispatch($readyQueue, $readyCustomer, 'ready', $locale);
                    }
                }
            } catch (Throwable $e) {
                report($e);
            }

            return response()->json([
                'success' => true,
                'message' => 'Next customer called',
                'data' => $nextQueue->load(['appointment.customer', 'appointment.staff']),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to advance queue',
            ], 500);
        }
    }
}
