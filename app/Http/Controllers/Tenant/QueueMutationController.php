<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Queue\Actions\AddExistingAppointmentToQueue;
use App\Application\Queue\Actions\SkipQueueEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class QueueMutationController extends Controller
{
    public function __construct(
        private readonly AddExistingAppointmentToQueue $addExistingAppointmentToQueue,
        private readonly SkipQueueEntry $skipQueueEntry,
    ) {}

    public function add(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        try {
            $result = $this->addExistingAppointmentToQueue->execute((int) $request->input('appointment_id'));
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Invalid operation',
                'message' => collect($e->errors())->flatten()->first() ?? 'Invalid operation',
            ], 400);
        }

        $queue = $result['queue'];

        if (! $result['created']) {
            return response()->json([
                'error' => 'Appointment already in queue',
                'message' => 'This appointment is already added to the queue',
                'data' => $queue,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Added to queue successfully',
            'data' => $queue->load(['appointment.customer', 'appointment.staff']),
        ], 201);
    }

    public function skip(Request $request, int $id)
    {
        try {
            $queue = $this->skipQueueEntry->execute($id);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Invalid operation';
            $status = str_contains(strtolower($message), 'not found') ? 404 : 400;

            return response()->json([
                'error' => 'Invalid operation',
                'message' => $message,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue entry skipped',
            'data' => $queue,
        ]);
    }
}
