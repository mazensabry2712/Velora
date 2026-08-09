<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueRepositoryInterface $queues,
    ) {}

    // ── Page views ───────────────────────────────────────────────────────

    public function days()
    {
        $days = \App\Models\Queue::selectRaw("
                DATE(created_at) as date,
                MAX(created_at) as last_activity,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'waiting'   THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'serving'   THEN 1 ELSE 0 END) as serving,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN is_vip = 1           THEN 1 ELSE 0 END) as vip
            ")
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) DESC')
            ->get();

        $today = now()->toDateString();
        if (!$days->first(fn ($d) => $d->date === $today)) {
            $days->prepend((object) [
                'date' => $today, 'last_activity' => null,
                'total' => 0, 'waiting' => 0, 'serving' => 0, 'completed' => 0, 'vip' => 0,
            ]);
        }

        $overallStats = $this->queues->getOverallStats();

        return view('admin.queue.days', compact('days', 'overallStats'));
    }

    public function show(string $date = null)
    {
        $date   = $date ?? now()->toDateString();
        $queues = $this->queues->getByDate($date);

        return view('admin.queue.index', compact('queues', 'date'));
    }

    public function print(string $date = null)
    {
        $date   = $date ?? now()->toDateString();
        $queues = $this->queues->getByDate($date);

        return view('admin.queue.print', compact('queues', 'date'));
    }

    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\QueuesExport,
            'queue_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    // ── JSON API ─────────────────────────────────────────────────────────

    public function addDirect(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'customer_name'  => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email',
                'staff_id'       => 'required|exists:users,id',
                'service_id'     => 'required|exists:services,id',
                'is_priority'    => 'nullable|boolean',
                'notes'          => 'nullable|string|max:1000',
            ]);

            $email = $data['customer_email'] ?? $data['customer_phone'] . '@temp.local';

            $customer = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $data['customer_name'],
                    'phone'    => $data['customer_phone'],
                    'password' => bcrypt(Str::random(32)),
                ]
            );

            if (!$customer->hasRole('Customer')) {
                $customer->assignRole('Customer');
            }

            $customer->update(['name' => $data['customer_name'], 'phone' => $data['customer_phone']]);

            $service     = Service::find($data['service_id']);

            // ── Business rule: queue max size ─────────────────────────────
            $maxSize = (int) BusinessRule::getValue(BusinessRule::QUEUE_MAX_SIZE, 0);
            if ($maxSize > 0) {
                $currentSize = \App\Models\Queue::whereDate('created_at', today())
                    ->whereIn('status', ['waiting', 'serving'])
                    ->count();
                if ($currentSize >= $maxSize) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Queue is full. Maximum size of :max has been reached.', ['max' => $maxSize]),
                    ], 422);
                }
            }

            $appointment = Appointment::create([
                'customer_id'  => $customer->id,
                'staff_id'     => $data['staff_id'],
                'service_id'   => $data['service_id'],
                'date'         => now()->toDateString(),
                'time_slot'    => now()->format('H:i'),
                'status'       => 'pending',
                'service_type' => $service?->name,
            ]);

            $queue = $this->queues->create([
                'appointment_id' => $appointment->id,
                'queue_number'   => \App\Models\Queue::generateQueueNumber(),
                'status'         => 'waiting',
                'is_vip'         => $data['is_priority'] ?? false,
                'notes'          => $data['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Added to queue. Number: #') . $queue->queue_number,
                'data'    => $queue->load(['appointment.customer', 'appointment.staff']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('addDirect queue: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function callNext(): JsonResponse
    {
        try {
            $next = $this->queues->callNext();

            if (!$next) {
                return response()->json(['success' => false, 'message' => __('No one waiting.')]);
            }

            return response()->json([
                'success' => true,
                'message' => '#' . $next->queue_number . ' - ' . ($next->appointment?->customer?->name ?? '-'),
                'data'    => $next->load(['appointment.customer']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function get(int $id): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::with(['appointment.customer', 'appointment.staff', 'appointment.service'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $queue]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Not found')], 404);
        }
    }

    public function updateEntry(Request $request, int $id): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::with('appointment.customer')->findOrFail($id);

            if ($queue->appointment?->customer) {
                $queue->appointment->customer->update([
                    'name'  => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'email' => $request->customer_email ?: null,
                ]);
            }

            $this->queues->update($queue, [
                'is_vip' => (bool) $request->is_vip,
                'notes'  => $request->notes ?: null,
            ]);

            return response()->json(['success' => true, 'message' => __('Updated.')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function serve(int $id): JsonResponse
    {
        return $this->transition($id, 'serving', __('Now serving.'));
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);
            $this->queues->update($queue, ['status' => 'completed']);
            // Note: Queue::booted() updating observer syncs appointment status automatically

            return response()->json(['success' => true, 'message' => __('Completed.')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function returnToWaiting(int $id): JsonResponse
    {
        return $this->transition($id, 'waiting', __('Returned to waiting.'));
    }

    public function setPriority(Request $request, int $id): JsonResponse
    {
        $request->validate(['priority' => 'required|boolean']);

        try {
            $queue = \App\Models\Queue::findOrFail($id);
            $this->queues->update($queue, ['is_vip' => $request->boolean('priority')]);

            return response()->json([
                'success' => true,
                'message' => $request->boolean('priority') ? __('Priority set.') : __('Priority removed.'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function remove(int $id): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);
            $this->queues->delete($queue);

            return response()->json(['success' => true, 'message' => __('Removed from queue.')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function moveToNextDay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date'   => 'required|date',
            'status' => 'required|in:waiting,serving',
        ]);

        try {
            $count   = $this->queues->moveToNextDay($data['date'], $data['status']);
            $nextDay = \Carbon\Carbon::parse($data['date'])->addDay()->format('Y-m-d');

            if ($count === 0) {
                return response()->json(['success' => false, 'message' => __('Nothing to move.')]);
            }

            return response()->json([
                'success' => true,
                'message' => "{$count} items moved to {$nextDay}",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function transition(int $id, string $status, string $message): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);
            $this->queues->update($queue, ['status' => $status]);

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
