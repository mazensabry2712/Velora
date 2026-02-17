<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\Appointment;
use App\Models\User;
use App\Jobs\SendQueueNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    /**
     * Display a listing of queues
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $date = $request->input('date', now()->toDateString());

        $queues = Queue::whereDate('created_at', $date)
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->with(['appointment.customer', 'appointment.staff'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->get();

        // Calculate estimated wait time for each queue
        $queues = $queues->map(function ($queue) {
            $queue->estimated_wait_time = $this->calculateEstimatedWaitTime($queue);
            return $queue;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $queues->count(),
                'waiting' => $queues->where('status', 'waiting')->count(),
                'current' => $queues->where('status', 'serving')->first(),
                'queues' => $queues,
            ]
        ]);
    }

    /**
     * Add appointment to queue
     */
    public function add(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);

        // Check if appointment already in queue
        $existingQueue = Queue::where('appointment_id', $appointment->id)->first();
        if ($existingQueue) {
            return response()->json([
                'error' => 'Appointment already in queue',
                'message' => 'This appointment is already added to the queue',
                'data' => $existingQueue
            ], 400);
        }

        // Check if customer is VIP
        $customer = User::find($appointment->customer_id);
        $isVip = $customer->is_vip ?? false;

        // Create queue entry with formatted queue number
        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => Queue::generateQueueNumber(),
            'status' => 'waiting',
            'is_vip' => $isVip,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to queue successfully',
            'data' => $queue->load(['appointment.customer', 'appointment.staff'])
        ], 201);
    }

    /**
     * Call next in queue
     */
    public function next(Request $request)
    {
        // Mark current serving as served
        Queue::where('status', 'serving')
            ->whereDate('created_at', now()->toDateString())
            ->update(['status' => 'completed']);

        // Get next queue entry (VIP priority first, then by queue number)
        $nextQueue = Queue::where('status', 'waiting')
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->first();

        if (!$nextQueue) {
            return response()->json([
                'success' => false,
                'message' => 'No waiting customers in queue'
            ], 404);
        }

        // Update status to serving
        $nextQueue->update([
            'status' => 'serving',
        ]);

        // Update appointment status to Confirmed
        $nextQueue->appointment->update(['status' => 'Confirmed']);

        // Send notification to the customer
        try {
            $customer = $nextQueue->appointment->customer;
            $tenant = tenant();
            $locale = $tenant->settings->language ?? 'en';
            SendQueueNotification::dispatch($nextQueue, $customer, 'next', $locale);

            // Check if there's someone ready next (only 1 person ahead)
            $readyQueue = Queue::where('status', 'waiting')
                ->whereDate('created_at', now()->toDateString())
                ->orderBy('is_vip', 'desc')
                ->orderBy('queue_number', 'asc')
                ->first();

            if ($readyQueue) {
                $readyCustomer = $readyQueue->appointment->customer;
                SendQueueNotification::dispatch($readyQueue, $readyCustomer, 'ready', $locale);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Queue notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Next customer called',
            'data' => $nextQueue->load(['appointment.customer', 'appointment.staff'])
        ]);
    }

    /**
     * Set or update VIP status
     */
    public function priority(Request $request)
    {
        $request->validate([
            'queue_id' => 'required|exists:queues,id',
            'is_vip' => 'required|boolean',
        ]);

        $queue = Queue::findOrFail($request->queue_id);

        if ($queue->status !== 'waiting') {
            return response()->json([
                'error' => 'Invalid operation',
                'message' => 'Can only change priority for waiting queues'
            ], 400);
        }

        $queue->update(['is_vip' => $request->is_vip]);

        return response()->json([
            'success' => true,
            'message' => 'Priority updated successfully',
            'data' => $queue->load(['appointment.customer', 'appointment.staff'])
        ]);
    }

    /**
     * Skip queue entry
     */
    public function skip(Request $request, $id)
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status !== 'waiting') {
            return response()->json([
                'error' => 'Invalid operation',
                'message' => 'Can only skip waiting queues'
            ], 400);
        }

        $queue->update(['status' => 'cancelled']);

        // Send notification to the customer
        try {
            $customer = $queue->appointment->customer;
            $tenant = tenant();
            $locale = $tenant->settings->language ?? 'en';
            SendQueueNotification::dispatch($queue, $customer, 'cancelled', $locale);
        } catch (\Exception $e) {
            \Log::error('Queue skip notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue entry skipped',
            'data' => $queue
        ]);
    }

    /**
     * Get queue by status
     */
    public function byStatus($status)
    {
        $queues = Queue::where('status', $status)
            ->whereDate('created_at', now()->toDateString())
            ->with(['appointment.customer', 'appointment.staff'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $queues
        ]);
    }

    /**
     * Get customer's queue status
     */
    public function myQueue(Request $request)
    {
        $user = $request->user();

        $queue = Queue::whereHas('appointment', function ($query) use ($user) {
                $query->where('customer_id', $user->id);
            })
            ->where('status', 'waiting')
            ->whereDate('created_at', now()->toDateString())
            ->with(['appointment'])
            ->first();

        if (!$queue) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in the queue today'
            ], 404);
        }

        // Calculate position in queue
        $position = Queue::where('status', 'waiting')
            ->whereDate('created_at', now()->toDateString())
            ->where(function ($query) use ($queue) {
                $query->where('is_vip', '>', $queue->is_vip)
                    ->orWhere(function ($q) use ($queue) {
                        $q->where('is_vip', $queue->is_vip)
                            ->where('queue_number', '<', $queue->queue_number);
                    });
            })
            ->count() + 1;

        $queue->position = $position;
        $queue->estimated_wait_time = $this->calculateEstimatedWaitTime($queue);

        return response()->json([
            'success' => true,
            'data' => [
                'queue' => $queue,
                'position' => $position,
                'estimated_wait_time' => $queue->estimated_wait_time,
                'is_vip' => $queue->is_vip,
            ]
        ]);
    }

    /**
     * Calculate estimated wait time
     */
    private function calculateEstimatedWaitTime($queue = null, $isVip = false)
    {
        // Average service time per customer (in minutes)
        $avgServiceTime = 15;

        if ($queue) {
            // Count queues ahead with higher priority
            $queuesAhead = Queue::where('status', 'waiting')
                ->whereDate('created_at', now()->toDateString())
                ->where(function ($q) use ($queue) {
                    $q->where('is_vip', '>', $queue->is_vip)
                        ->orWhere(function ($query) use ($queue) {
                            $query->where('is_vip', $queue->is_vip)
                                ->where('queue_number', '<', $queue->queue_number);
                        });
                })
                ->count();
        } else {
            // For new queue, count all with higher or equal priority
            $queuesAhead = Queue::where('status', 'waiting')
                ->whereDate('created_at', now()->toDateString())
                ->where('is_vip', '>=', $isVip)
                ->count();
        }

        // Calculate estimated time
        $estimatedMinutes = $queuesAhead * $avgServiceTime;

        return $estimatedMinutes;
    }

    /**
     * Get queue status by queue number (for public display)
     */
    public function getQueueStatus($queueNumber)
    {
        $queue = Queue::where('queue_number', $queueNumber)
            ->whereDate('created_at', now()->toDateString())
            ->with(['appointment.customer'])
            ->first();

        if (!$queue) {
            return response()->json([
                'success' => false,
                'message' => 'Queue not found for today'
            ], 404);
        }

        // Count people ahead
        $peopleAhead = Queue::where('status', 'waiting')
            ->whereDate('created_at', now()->toDateString())
            ->where(function ($q) use ($queue) {
                $q->where('is_vip', '>', $queue->is_vip)
                    ->orWhere(function ($query) use ($queue) {
                        $query->where('is_vip', $queue->is_vip)
                            ->where('queue_number', '<', $queue->queue_number);
                    });
            })
            ->count();

        // Get currently serving
        $currentlyServing = Queue::where('status', 'serving')
            ->whereDate('created_at', now()->toDateString())
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'queue_number' => $queue->queue_number,
                'status' => $queue->status,
                'is_vip' => $queue->is_vip,
                'people_ahead' => $peopleAhead,
                'estimated_wait_time' => $this->calculateEstimatedWaitTime($queue),
                'currently_serving' => $currentlyServing ? $currentlyServing->queue_number : null,
                'customer_name' => $queue->appointment->customer->name ?? 'N/A',
            ]
        ]);
    }
}
