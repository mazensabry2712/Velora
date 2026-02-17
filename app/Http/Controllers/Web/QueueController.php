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
}
