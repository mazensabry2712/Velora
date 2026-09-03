<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class QueueController extends Controller
{
    public function dashboard()
    {
        return view('queue.dashboard');
    }

    public function publicQueue()
    {
        $queues = Queue::whereIn('status', ['waiting', 'serving'])
            ->where(function ($query) {
                $query->whereDate('queue_date', today())
                    ->orWhere(fn ($fallback) => $fallback->whereNull('queue_date')->whereDate('created_at', today()));
            })
            ->orderByDesc('is_vip')
            ->orderBy('queue_number')
            ->get();

        $current = $queues->firstWhere('status', 'serving');
        $waiting = $queues->where('status', 'waiting')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'current' => $current ? ['queue_number' => $current->queue_number] : null,
                'queues' => $waiting->map(fn (Queue $queue) => [
                    'queue_number' => $queue->queue_number,
                    'status' => $queue->status,
                ]),
                'total_waiting' => $waiting->count(),
            ],
        ]);
    }

    public function getQueueStatus(string $identifier)
    {
        $identifier = trim($identifier);
        $rateLimitKey = 'public-queue-status:' . tenant()?->getTenantKey() . ':' . request()->ip();
        $isReference = str_starts_with(strtoupper($identifier), 'VL-');

        if (RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
            return response()->json(['success' => false, 'message' => __('Too many requests. Please try again later.')], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        try {
            $query = Queue::query()->with([
                'appointment.service',
                'appointment.staff',
                'appointment.customer',
            ]);

            if ($isReference) {
                $query->whereHas('appointment', fn ($appointment) => $appointment->where('public_reference', strtoupper($identifier)));
            } else {
                $query->where('queue_number', $identifier);
            }

            $queue = $query->first();
            if (! $queue) {
                return response()->json(['success' => false, 'message' => __('Appointment reference not found')], 404);
            }

            $appointment = $queue->appointment;
            $staffName = $appointment?->staff?->full_name ?: __('Not assigned');
            $serviceDuration = (int) ($appointment?->service?->duration_minutes ?? 0);
            $peopleAhead = null;

            if (in_array($queue->status, ['waiting', 'serving'], true) && $queue->queue_date) {
                if ($queue->status === 'serving') {
                    $peopleAhead = 0;
                } else {
                    $active = Queue::query()
                        ->where('queue_date', $queue->queue_date)
                        ->whereIn('status', ['waiting', 'serving'])
                        ->orderByDesc('is_vip')
                        ->orderBy('id')
                        ->get(['id', 'status']);
                    $position = $active->search(fn (Queue $item) => $item->id === $queue->id);
                    $peopleAhead = $position === false ? 0 : $active->slice(0, $position)->where('status', 'waiting')->count();
                }
            }

            $data = [
                'reference' => $appointment?->public_reference,
                'queue_number' => $queue->queue_number,
                'service' => $appointment?->service?->name ?? __('Not available'),
                'staff_name' => $staffName,
                'status' => $queue->status,
                'is_vip' => (bool) $queue->is_vip,
                'queue_date' => $queue->queue_date?->toDateString(),
                'appointment_date' => $appointment?->starts_at?->format('Y-m-d'),
                'appointment_time' => $appointment?->starts_at?->format('H:i'),
                'duration_minutes' => $serviceDuration ?: null,
                'people_ahead' => $peopleAhead,
                'estimated_wait_minutes' => ($peopleAhead !== null && $peopleAhead > 0 && $serviceDuration > 0) ? $peopleAhead * $serviceDuration : 0,
                'updated_at' => now()->toIso8601String(),
            ];

            if ($isReference) {
                $customer = $appointment?->customer;
                $data['customer_name'] = $customer?->full_name;
                $data['tracking_url'] = route('customer.queue.status', ['ref' => $appointment?->public_reference]);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $exception) {
            Log::error('Public queue status error', [
                'tenant_id' => tenant()?->getTenantKey(),
                'message' => $exception->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => __('Error loading queue status')], 500);
        }
    }
}
