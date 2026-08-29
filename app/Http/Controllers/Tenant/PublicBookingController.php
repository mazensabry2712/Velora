<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Booking\Actions\CreatePublicBooking;
use App\Application\Booking\DTOs\PublicBookingData;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PublicBookingRequest;
use App\Mail\PublicAppointmentConfirmationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class PublicBookingController extends Controller
{
    public function __construct(private readonly CreatePublicBooking $createPublicBooking) {}

    public function store(PublicBookingRequest $request): JsonResponse
    {
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
            $reference = (string) $appointment->public_reference;
            $trackingUrl = route('customer.queue.status', ['ref' => $reference]);

            try {
                $appointment->loadMissing(['service', 'newStaff']);

                $serviceName = (string) ($appointment->service_name ?? $appointment->service?->name ?? 'Appointment');
                $staffName = (string) ($appointment->newStaff?->full_name ?? trim(($appointment->newStaff?->first_name ?? '') . ' ' . ($appointment->newStaff?->last_name ?? '')));
                $tenantName = (string) (tenant()?->name ?? config('app.name'));
                $locale = app()->getLocale() ?: 'en';

                if ($customer->email) {
                    Mail::to($customer->email)->queue(new PublicAppointmentConfirmationMail(
                        tenantName: $tenantName,
                        customerName: trim($customer->first_name . ' ' . $customer->last_name),
                        serviceName: $serviceName,
                        staffName: $staffName !== '' ? $staffName : '—',
                        appointmentDate: $appointment->date?->format('Y-m-d') ?? '',
                        appointmentTime: $appointment->time_slot ?? '',
                        duration: (string) ($appointment->service?->duration ?? $appointment->service?->duration_minutes ?? ''),
                        queueNumber: (string) $queue->queue_number,
                        reference: $reference,
                        trackingUrl: $trackingUrl,
                        locale: $locale,
                    ));
                }
            } catch (\Throwable $notificationException) {
                Log::warning('Public appointment confirmation email could not be queued.', [
                    'tenant_id' => tenant()?->getTenantKey(),
                    'public_reference' => $reference,
                    'message' => $notificationException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => __('Appointment booked successfully'),
                'data' => [
                    'appointment' => [
                        'public_reference' => $reference,
                        'service_id' => $appointment->service_id,
                        'staff_id' => $appointment->staff_id_new,
                        'starts_at' => $appointment->starts_at?->toIso8601String(),
                        'ends_at' => $appointment->ends_at?->toIso8601String(),
                        'timezone' => $appointment->timezone,
                        'status' => $appointment->status,
                        'source' => $appointment->source,
                    ],
                    'tracking_url' => $trackingUrl,
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
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => __('Validation error'), 'errors' => $exception->errors()], 422);
        } catch (SlotUnavailableException $exception) {
            return response()->json(['success' => false, 'message' => __('Slot not available'), 'reason' => $exception->getMessage()], 409);
        } catch (\Throwable $exception) {
            Log::error('Public booking error', [
                'tenant_id' => tenant()?->getTenantKey(),
                'message' => $exception->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => __('An error occurred while booking the appointment')], 500);
        }
    }
}
