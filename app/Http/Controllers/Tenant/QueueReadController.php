<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Queue\Actions\GetCustomerQueue;
use App\Application\Queue\Actions\GetQueueOverview;
use App\Application\Queue\Actions\GetQueueStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QueueReadController extends Controller
{
    public function __construct(
        private readonly GetQueueOverview $getQueueOverview,
        private readonly GetCustomerQueue $getCustomerQueue,
        private readonly GetQueueStatus $getQueueStatus,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $date = $request->string('date')->trim()->value() ?: now()->toDateString();
        $status = $request->string('status')->trim()->value() ?: null;

        return response()->json($this->getQueueOverview->execute($date, $status));
    }

    public function myQueue(Request $request): JsonResponse
    {
        $date = $request->string('date')->trim()->value() ?: now()->toDateString();

        return response()->json($this->getCustomerQueue->execute($request->user(), $date));
    }

    public function status(Request $request, string $queueNumber): JsonResponse
    {
        $date = $request->string('date')->trim()->value() ?: now()->toDateString();

        return response()->json($this->getQueueStatus->execute($queueNumber, $date));
    }
}
