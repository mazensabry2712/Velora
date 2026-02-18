<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Illuminate\Http\Request;

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
        // Get all active queues (waiting or serving) - same as admin view
        $queues = Queue::whereIn('status', ['waiting', 'serving'])
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
        try {
            $queue = Queue::where('queue_number', $queueNumber)
                ->with(['appointment.customer', 'appointment.service', 'appointment.staff'])
                ->first();

            if (!$queue) {
                return response()->json([
                    'success' => false,
                    'message' => __('Queue number not found')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_number' => $queue->queue_number,
                    'customer_name' => $queue->appointment?->customer?->name ?? 'N/A',
                    'service' => $queue->appointment?->service?->name ?? 'N/A',
                    'staff_name' => $queue->appointment?->staff?->name ?? 'N/A',
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
