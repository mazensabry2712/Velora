<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Queue\Actions\GetCustomerQueue;
use App\Application\Queue\Actions\GetQueueOverview;
use App\Application\Queue\Actions\GetQueueStatus;
use App\Domain\Queue\Contracts\QueueReader;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QueueReadController extends Controller
{
    public function __construct(
        private readonly GetQueueOverview $getQueueOverview,
        private readonly GetCustomerQueue $getCustomerQueue,
        private readonly GetQueueStatus $getQueueStatus,
        private readonly QueueReader $queues,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $date = $request->string('date')->trim()->value() ?: now()->toDateString();
        $status = $request->string('status')->trim()->value() ?: null;
        $result = $this->getQueueOverview->execute($date, $status);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function byStatus(string $status): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->queues->forDate(now()->toDateString(), $status),
        ]);
    }

    public function myQueue(Request $request): JsonResponse
    {
        $date = $request->string('date')->trim()->value() ?: now()->toDateString();
        $result = $this->getCustomerQueue->execute($request->user(), $date);

        if (! $result['queue']) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in the queue today',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function status(Request $request, string $queueNumber): JsonResponse
    {
        $date = $request->string('date')->trim()->value() ?: now()->toDateString();
        $result = $this->getQueueStatus->execute($queueNumber, $date);

        if (! $result['queue']) {
            return response()->json([
                'success' => false,
                'message' => 'Queue not found for today',
            ], 404);
        }

        $queue = $result['queue'];

        return response()->json([
            'success' => true,
            'data' => [
                'queue_number' => $queue->queue_number,
                'status' => $queue->status,
                'is_vip' => $queue->is_vip,
                'people_ahead' => $result['people_ahead'],
                'estimated_wait_time' => $result['estimated_wait_time'],
                'currently_serving' => $result['currently_serving'],
                'service' => $queue->appointment?->service?->name,
                'staff_name' => $queue->appointment?->staff?->name,
            ],
        ]);
    }
}
