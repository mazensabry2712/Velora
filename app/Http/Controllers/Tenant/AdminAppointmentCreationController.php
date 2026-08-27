<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Booking\Actions\CreateAdminAppointment;
use App\Application\Booking\DTOs\CreateAdminAppointmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppointmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AdminAppointmentCreationController extends Controller
{
    public function __construct(
        private readonly CreateAdminAppointment $createAdminAppointment,
    ) {}

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->createAdminAppointment->execute(
                new CreateAdminAppointmentData(
                    customerName: $data['customer_name'],
                    customerPhone: $data['customer_phone'],
                    customerEmail: $data['customer_email'] ?? null,
                    staffId: isset($data['staff_id']) ? (int) $data['staff_id'] : auth()->id(),
                    serviceId: isset($data['service_id']) ? (int) $data['service_id'] : null,
                    appointmentDate: $data['appointment_date'],
                    appointmentTime: $data['appointment_time'],
                    serviceType: $data['service_type'] ?? null,
                    notes: $data['notes'] ?? null,
                    addToQueue: $request->boolean('add_to_queue'),
                    queueDate: $data['queue_date'] ?? null,
                ),
            );

            return response()->json([
                'success' => true,
                'message' => __('Appointment saved successfully.'),
                'data' => $result['appointment'],
            ], 201);
        } catch (Throwable $exception) {
            Log::error('createAdminAppointment: ' . $exception->getMessage(), [
                'tenant_id' => tenant()?->getTenantKey(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
