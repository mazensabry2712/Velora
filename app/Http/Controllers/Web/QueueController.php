<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class QueueController extends Controller
{
    /**
     * Show public queue dashboard
     */
    public function dashboard()
    {
        return view('queue.dashboard');
    }

    /**
     * Get public queue data (API endpoint - no auth required)
     * Returns only necessary info for public display (no sensitive data)
     */
    public function publicQueue()
    {
        // Get all active queues (waiting or serving) for today only
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

        // Get current serving
        $current = $queues->where('status', 'serving')->first();

        // Get waiting queues (only return queue numbers for privacy)
        $waitingQueues = $queues->where('status', 'waiting')->map(function ($queue) {
            return [
                'queue_number' => $queue->queue_number,
                'status' => $queue->status,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'current' => $current ? [
                    'queue_number' => $current->queue_number,
                ] : null,
                'queues' => $waitingQueues,
                'total_waiting' => $waitingQueues->count(),
            ]
        ]);
    }

    /**
     * Get specific queue status by queue number
     * Public endpoint for customers to check their queue status
     */
    public function getQueueStatus($queueNumber)
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
            $queue = Queue::where('queue_number', $queueNumber)
                ->with([
                    'appointment.customer',
                    'appointment.newCustomer',
                    'appointment.service',
                    'appointment.staff',
                    'appointment.newStaff',
                ])
                ->first();

            if (!$queue) {
                return response()->json([
                    'success' => false,
                    'message' => __('Queue number not found')
                ], 404);
            }

            $appointment = $queue->appointment;
            $customerName = $appointment?->newCustomer?->full_name
                ?: $appointment?->customer?->name
                ?: 'N/A';
            $staffName = $appointment?->newStaff?->full_name
                ?: $appointment?->staff?->name
                ?: 'N/A';

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_number' => $queue->queue_number,
                    'customer_name' => $customerName,
                    'service' => $appointment?->service?->name ?? 'N/A',
                    'staff_name' => $staffName,
                    'status' => $queue->status,
                    'is_vip' => $queue->is_vip,
                    'notes' => $queue->notes,
                    'queue_date' => $queue->queue_date?->format('Y-m-d') ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getQueueStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Error loading queue status')
            ], 500);
        }
    }
}
