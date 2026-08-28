<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Queue\Actions\AddDirectQueueEntry;
use App\Application\Queue\Actions\CallNextQueueEntry;
use App\Application\Queue\Actions\TransitionQueueEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddDirectQueueEntryRequest;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class QueueController extends Controller
{
    public function __construct(
        private readonly QueueRepositoryInterface $queues,
        private readonly AddDirectQueueEntry $addDirectQueueEntry,
        private readonly CallNextQueueEntry $callNextQueueEntry,
        private readonly TransitionQueueEntry $transitionQueueEntry,
    ) {}

    public function days()
    {
        $days = \App\Models\Queue::selectRaw("
                DATE(created_at) as date,
                MAX(created_at) as last_activity,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'serving' THEN 1 ELSE 0 END) as serving,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN is_vip = 1 THEN 1 ELSE 0 END) as vip
            ")
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) DESC')
            ->get();

        $today = now()->toDateString();
        if (! $days->first(fn ($day) => $day->date === $today)) {
            $days->prepend((object) [
                'date' => $today,
                'last_activity' => null,
                'total' => 0,
                'waiting' => 0,
                'serving' => 0,
                'completed' => 0,
                'vip' => 0,
            ]);
        }

        return view('admin.queue.days', [
            'days' => $days,
            'overallStats' => $this->queues->getOverallStats(),
        ]);
    }

    public function show(?string $date = null)
    {
        $date = $date ?? now()->toDateString();

        return view('admin.queue.index', [
            'queues' => $this->queues->getByDate($date),
            'date' => $date,
        ]);
    }

    public function print(?string $date = null)
    {
        $date = $date ?? now()->toDateString();

        return view('admin.queue.print', [
            'queues' => $this->queues->getByDate($date),
            'date' => $date,
        ]);
    }

    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\QueuesExport,
            'queue_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    public function addDirect(AddDirectQueueEntryRequest $request): JsonResponse
    {
        try {
            $queue = $this->addDirectQueueEntry->execute($request->validated());

            return response()->json([
                'success' => true,
                'message' => __('Added to queue. Number: #') . $queue->queue_number,
                'data' => $queue->load(['appointment.customer', 'appointment.staff']),
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function callNext(): JsonResponse
    {
        try {
            $next = $this->callNextQueueEntry->execute();

            if (! $next) {
                return response()->json(['success' => false, 'message' => __('No one waiting.')]);
            }

            return response()->json([
                'success' => true,
                'message' => '#' . $next->queue_number . ' - ' . ($next->appointment?->customer?->name ?? '-'),
                'data' => $next->load(['appointment.customer']),
            ]);
        } catch (Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function get(int $id): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::with([
                'appointment.customer',
                'appointment.staff',
                'appointment.service',
            ])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $queue]);
        } catch (Throwable) {
            return response()->json(['success' => false, 'message' => __('Not found')], 404);
        }
    }

    public function updateEntry(Request $request, int $id): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::with('appointment.customer')->findOrFail($id);

            if ($queue->appointment?->customer) {
                $queue->appointment->customer->update([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'email' => $request->customer_email ?: null,
                ]);
            }

            $this->queues->update($queue, [
                'is_vip' => (bool) $request->is_vip,
                'notes' => $request->notes ?: null,
            ]);

            return response()->json(['success' => true, 'message' => __('Updated.')]);
        } catch (Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function serve(int $id): JsonResponse
    {
        return $this->transition($id, 'serving', __('Now serving.'));
    }

    public function complete(int $id): JsonResponse
    {
        return $this->transition($id, 'completed', __('Completed.'));
    }

    public function returnToWaiting(int $id): JsonResponse
    {
        return $this->transition($id, 'waiting', __('Returned to waiting.'));
    }

    /**
     * Backward-compatible alias for the legacy route that still targets
     * `priority`, while the canonical implementation is `setPriority`.
     */
    public function priority(Request $request, int $id): JsonResponse
    {
        return $this->setPriority($request, $id);
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
        } catch (Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function remove(int $id): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);
            $this->queues->delete($queue);

            return response()->json(['success' => true, 'message' => __('Removed from queue.')]);
        } catch (Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function moveToNextDay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:waiting,serving',
        ]);

        try {
            $count = $this->queues->moveToNextDay($data['date'], $data['status']);
            $nextDay = Carbon::parse($data['date'])->addDay()->format('Y-m-d');

            if ($count === 0) {
                return response()->json(['success' => false, 'message' => __('Nothing to move.')]);
            }

            return response()->json([
                'success' => true,
                'message' => "{$count} items moved to {$nextDay}",
            ]);
        } catch (Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    private function transition(int $id, string $status, string $message): JsonResponse
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);
            $queue = $this->transitionQueueEntry->execute($queue, $status);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $queue,
            ]);
        } catch (Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
