<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Booking\Actions\CreatePublicBooking;
use App\Application\Booking\DTOs\PublicBookingData;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PublicBookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class PublicBookingController extends Controller
{
    public function __construct(
        private readonly CreatePublicBooking $createPublicBooking,
    ) {}

    public function store(PublicBookingRequest $request): JsonResponse
    {
        $tenantId = (string) tenant()->getTenantKey();

        try {
            $validated = $request->validated();
            $data = new PublicBookingData(
                customerName: strip_tags(trim($validated['customer_name'])),
                customerEmail: $validated['customer_email'],
                customerPhone: preg_replace('/[^\d\+\-\(\)\s]/', '', $validated['customer_phone']),
                serviceId: (int) $validated['service_id'],
                staffUserId: (int) $validated['staff_id'],
                resourceId: isset($validated['resource_id']) ? (int) $validated['resource_id'] : null,
                appointmentDate: $validated['appointment_date'],
                appointmentTime: $validated['appointment_time'],
                requestedTimezone: $validated['timezone'] ?? null,
                notes: ! empty($validated['notes']) ? strip_tags(trim($validated['notes'])) : null,
            );

            $result = $this->createPublicBooking->execute($data);
            $appointment = $result['appointment'];
            $queue = $result['queue'];
            $customer = $result['customer'];

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'data' => [
                    'appointment' => [
                        'public_reference' => $appointment->public_reference,
                        'service_id' => $appointment->service_id,
                        'staff_id' => $appointment->staff_id_new,
                        'starts_at' => $appointment->starts_at?->toIso8601String(),
                        'ends_at' => $appointment->ends_at?->toIso8601String(),
                        'timezone' => $appointment->timezone,
                        'status' => $appointment->status,
                        'source' => $appointment->source,
                    ],
                    'queue_number' => $queue->queue_number,
                    'queue' => [
                        'queue_number' => $queue->queue_number,
                        'queue_date' => $queue->queue_date?->toDateString(),
                        'status' => $queue->status,
                    ],
                    'customer' => [
                        'name' => trim($customer->first_name . ' ' . $customer->last_name),
                    ],
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $exception->errors(),
            ], 422);
        } catch (SlotUnavailableException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Slot not available',
                'reason' => $exception->getMessage(),
            ], 409);
        } catch (\Throwable $exception) {
            Log::error('Public booking error: ' . $exception->getMessage(), [
                'tenant_id' => $tenantId,
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the appointment',
            ], 500);
        }
    }
}
