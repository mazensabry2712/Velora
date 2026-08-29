<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class QueueController extends Controller
{
    public function dashboard()
    {
        return view('queue.dashboard');
    }

    public function publicQueue()
    {
        $queues = Queue::whereIn('status', ['waiting', 'serving'])
            ->where(function ($q) {
                $q->whereDate('queue_date', today())
                  ->orWhere(function ($q2) {
                      $q2->whereNull('queue_date')->whereDate('created_at', today());
                  });
            })
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->get();

        $current = $queues->where('status', 'serving')->first();

        $waitingQueues = $queues->where('status', 'waiting')->map(function ($queue) {
            return [
                'queue_number' => $queue->queue_number,
                'status' => $queue->status,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'current' => $current ? ['queue_number' => $current->queue_number] : null,
                'queues' => $waitingQueues,
                'total_waiting' => $waitingQueues->count(),
            ],
        ]);
    }

    /**
     * Public queue lookup accepts either the legacy queue number or the new
     * unguessable public appointment reference (VL-XXXXXXXX).
     */
    public function getQueueStatus(string $identifier)
    {
        $rateLimitKey = 'public-queue-status:' . tenant()?->getTenantKey() . ':' . request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
            return response()->json([
                'success' => false,
                'message' => __('Too many requests. Please try again later.'),
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $query = Queue::query()->with([
                'appointment.service',
                'appointment.staff',
                'appointment.newStaff',
                'appointment.newCustomer',
            ]);

            if (str_starts_with(strtoupper($identifier), 'VL-')) {
                $query->whereHas('appointment', fn ($appointment) =>
                    $appointment->where('public_reference', strtoupper(trim($identifier)))
                );
            } else {
                $query->where('queue_number', $identifier);
            }

            $queue = $query->first();

            if (!$queue) {
                return response()->json([
                    'success' => false,
                    'message' => __('Queue number not found'),
                ], 404);
            }

            $appointment = $queue->appointment;
            $staffName = $appointment?->newStaff?->full_name
                ?: $appointment?->staff?->name
                ?: 'N/A';
            $customer = $appointment?->newCustomer;
            $customerName = $customer
                ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                : null;

            $ahead = null;
            if (in_array($queue->status, ['waiting', 'serving'], true)) {
                $ahead = Queue::query()
                    ->where('queue_date', $queue->queue_date)
                    ->where('status', 'waiting')
                    ->where(function ($q) use ($queue) {
                        $q->where('is_vip', '>', (int) $queue->is_vip)
                            ->orWhere(function ($samePriority) use ($queue) {
                                $samePriority->where('is_vip', (int) $queue->is_vip)
                                    ->where('queue_number', '<', $queue->queue_number);
                            });
                    })
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reference' => $appointment?->public_reference,
                    'queue_number' => $queue->queue_number,
                    'service' => $appointment?->service?->name ?? 'N/A',
                    'staff_name' => $staffName,
                    'status' => $queue->status,
                    'is_vip' => $queue->is_vip,
                    'queue_date' => $queue->queue_date?->format('Y-m-d') ?? null,
                    'appointment_date' => $appointment?->starts_at?->format('Y-m-d'),
                    'appointment_time' => $appointment?->starts_at?->format('H:i'),
                    'duration_minutes' => $appointment?->service?->duration_minutes,
                    'customer_name' => $customerName,
                    'people_ahead' => $ahead,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error in getQueueStatus: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Error loading queue status'),
            ], 500);
        }
    }
}
