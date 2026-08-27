<?php

namespace App\Http\Controllers\Tenant;

use App\Application\Queue\Actions\SetQueuePriority;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\Appointment;
use App\Models\User;
use App\Jobs\SendQueueNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    public function __construct(
        private readonly SetQueuePriority $setQueuePriority,
    ) {}

    public function index(Request $request)
    {
        $status = $request->input('status');
        $date = $request->input('date', now()->toDateString());

        $queues = Queue::whereDate('created_at', $date)
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->with(['appointment.customer', 'appointment.staff'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->get()
            ->map(function ($queue) {
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
            ],
        ]);
    }

    public function add(Request $request)
    {
        $request->validate(['appointment_id' => 'required|exists:appointments,id']);

        $appointment = Appointment::findOrFail($request->appointment_id);
        $existingQueue = Queue::where('appointment_id', $appointment->id)->first();

        if ($existingQueue) {
            return response()->json([
                'error' => 'Appointment already in queue',
                'message' => 'This appointment is already added to the queue',
                'data' => $existingQueue,
            ], 400);
        }

        $customer = User::find($appointment->customer_id);
        $isVip = $customer->is_vip ?? false;

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => Queue::generateQueueNumber(),
            'status' => 'waiting',
            'is_vip' => $isVip,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to queue successfully',
            'data' => $queue->load(['appointment.customer', 'appointment.staff']),
        ], 201);
    }

    public function next(Request $request)
    {
        Queue::where('status', 'serving')
            ->whereDate('created_at', now()->toDateString())
            ->update(['status' => 'completed']);

        $nextQueue = Queue::where('status', 'waiting')
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->first();

        if (! $nextQueue) {
            return response()->json([
                'success' => false,
                'message' => 'No waiting customers in queue',
            ], 404);
        }

        $nextQueue->update(['status' => 'serving']);
        $nextQueue->appointment?->update(['status' => Appointment::STATUS_CONFIRMED]);

        try {
            $customer = $nextQueue->appointment?->customer;
            $tenant = tenant();
            $locale = $tenant->settings->language ?? 'en';
            SendQueueNotification::dispatch($nextQueue, $customer, 'next', $locale);

            $readyQueue = Queue::where('status', 'waiting')
                ->whereDate('created_at', now()->toDateString())
                ->orderBy('is_vip', 'desc')
                ->orderBy('queue_number', 'asc')
                ->first();

            if ($readyQueue) {
                $readyCustomer = $readyQueue->appointment?->customer;
                SendQueueNotification::dispatch($readyQueue, $readyCustomer, 'ready', $locale);
            }
        } catch (\Exception $e) {
            \Log::error('Queue notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Next customer called',
            'data' => $nextQueue->load(['appointment.customer', 'appointment.staff']),
        ]);
    }

    public function priority(Request $request)
    {
        $request->validate([
            'queue_id' => 'required|exists:queues,id',
            'is_vip' => 'required|boolean',
        ]);

        try {
            $queue = $this->setQueuePriority->execute(
                (int) $request->queue_id,
                (bool) $request->boolean('is_vip')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = $e->errors()['queue_id'][0] ?? 'Invalid operation';

            return response()->json([
                'error' => 'Invalid operation',
                'message' => $message,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Priority updated successfully',
            'data' => $queue->load(['appointment.customer', 'appointment.staff']),
        ]);
    }

    public function skip(Request $request, $id)
    {
        $queue = Queue::findOrFail($id);

        if ($queue->status !== 'waiting') {
            return response()->json([
                'error' => 'Invalid operation',
                'message' => 'Can only skip waiting queues',
            ], 400);
        }

        $queue->update(['status' => 'cancelled']);
        $queue->appointment?->update(['status' => Appointment::STATUS_CANCELLED]);

        try {
            $customer = $queue->appointment?->customer;
            $tenant = tenant();
            $locale = $tenant->settings->language ?? 'en';
            SendQueueNotification::dispatch($queue, $customer, 'cancelled', $locale);
        } catch (\Exception $e) {
            \Log::error('Queue skip notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue entry skipped',
            'data' => $queue,
        ]);
    }

    public function byStatus($status)
    {
        $queues = Queue::where('status', $status)
            ->whereDate('created_at', now()->toDateString())
            ->with(['appointment.customer', 'appointment.staff'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $queues]);
    }

    public function myQueue(Request $request)
    {
        $user = $request->user();
        $customer = Customer::query()->where('email', $user->email)->first();

        $query = Queue::query()
            ->whereHas('appointment', function ($query) use ($user, $customer) {
                $query->where(function ($q) use ($user, $customer) {
                    $q->where('customer_id', $user->id);
                    if ($customer) {
                        $q->orWhere('customer_id_new', $customer->id);
                    }
                });
            })
            ->where('status', 'waiting')
            ->whereDate('created_at', now()->toDateString())
            ->with(['appointment']);

        $queue = $query->first();

        if (! $queue) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in the queue today',
            ], 404);
        }

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
            ],
        ]);
    }

    private function calculateEstimatedWaitTime($queue = null, $isVip = false)
    {
        $avgServiceTime = 15;

        if ($queue) {
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
            $queuesAhead = Queue::where('status', 'waiting')
                ->whereDate('created_at', now()->toDateString())
                ->where('is_vip', '>=', $isVip)
                ->count();
        }

        return $queuesAhead * $avgServiceTime;
    }

    public function getQueueStatus($queueNumber)
    {
        $queue = Queue::where('queue_number', $queueNumber)
            ->whereDate('created_at', now()->toDateString())
            ->first();

        if (! $queue) {
            return response()->json([
                'success' => false,
                'message' => 'Queue not found for today',
            ], 404);
        }

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
                'currently_serving' => $currentlyServing?->queue_number,
                'service' => $queue->appointment?->service?->name,
                'staff_name' => $queue->appointment?->staff?->name,
            ],
        ]);
    }
}
