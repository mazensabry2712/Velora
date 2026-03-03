<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Booking\DTOs\CreateBookingData;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Booking\Services\BookingCreationService;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly BookingCreationService $bookingService,
    ) {}

    /**
     * Public booking endpoint — V2 engine.
     *
     * Creates or upserts a Customer V2 record, then delegates to
     * BookingCreationService which handles slot validation, pessimistic
     * locking, and business-rule checks.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_name'    => 'required|string|max:255|regex:/^[\p{L}\p{N}\s\-\.]+$/u',
                'customer_email'   => 'required|email|max:255',
                'customer_phone'   => 'required|string|max:20|regex:/^[\d\+\-\(\)\s]+$/',
                'appointment_date' => 'required|date|after_or_equal:today',
                'service_id'       => 'required|exists:services,id',
                'staff_id'         => 'required|exists:staff,id',
                'resource_id'      => 'nullable|exists:resources,id',
                'timezone'         => 'nullable|timezone',
                'notes'            => 'nullable|string|max:1000',
            ]);

            // ── Sanitize text inputs ──────────────────────────────────────
            $validated['customer_name']  = strip_tags(trim($validated['customer_name']));
            $validated['customer_phone'] = preg_replace('/[^\d\+\-\(\)\s]/', '', $validated['customer_phone']);
            if (! empty($validated['notes'])) {
                $validated['notes'] = strip_tags(trim($validated['notes']));
            }

            // ── Find or upsert Customer V2 ────────────────────────────────
            [$firstName, $lastName] = $this->splitName($validated['customer_name']);

            $customer = Customer::firstOrNew([
                'email' => $validated['customer_email'],
            ]);

            // Always keep phone & name fresh
            $customer->fill([
                'first_name'         => $firstName,
                'last_name'          => $lastName,
                'phone'              => $validated['customer_phone'],
                'acquisition_source' => $customer->exists ? $customer->acquisition_source : 'online',
            ]);
            $customer->save();

            // ── Build DTO & create appointment via V2 engine ─────────────
            $tz       = $validated['timezone'] ?? config('app.timezone');
            $startsAt = Carbon::parse($validated['appointment_date'], $tz);

            $data = new CreateBookingData(
                serviceId:  (int) $validated['service_id'],
                staffId:    (int) $validated['staff_id'],
                startsAt:   $startsAt,
                timezone:   $tz,
                customerId: $customer->id,
                resourceId: isset($validated['resource_id']) ? (int) $validated['resource_id'] : null,
                source:     'online',
                notes:      $validated['notes'] ?? null,
            );

            $appointment = $this->bookingService->create($data);

            // ── Auto-enqueue as walk-in ───────────────────────────────────
            $queue = Queue::create([
                'appointment_id' => $appointment->id,
                'queue_number'   => Queue::generateQueueNumber(),
                'status'         => 'waiting',
                'is_vip'         => false,
                'notes'          => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success'      => true,
                'message'      => 'Appointment booked successfully',
                'data'         => [
                    'appointment'  => $appointment,
                    'queue_number' => $queue->queue_number,
                    'customer'     => [
                        'id'    => $customer->id,
                        'name'  => $customer->first_name . ' ' . $customer->last_name,
                        'email' => $customer->email,
                    ],
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);

        } catch (SlotUnavailableException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slot not available',
                'reason'  => $e->getMessage(),
            ], 409);

        } catch (\Exception $e) {
            \Log::error('Public booking error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the appointment',
            ], 500);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function splitName(string $fullName): array
    {
        $parts     = explode(' ', trim($fullName), 2);
        $firstName = $parts[0];
        $lastName  = $parts[1] ?? '';
        return [$firstName, $lastName];
    }



    /**
     * List appointments (authenticated users) — ordered by starts_at desc.
     */
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::with(['customerNew:id,first_name,last_name,email', 'staffNew:id,name'])
            ->orderBy('starts_at', 'desc')
            ->paginate(20);

        return response()->json($appointments);
    }

    /**
     * Show a specific appointment.
     */
    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['customerNew', 'staffNew'])->findOrFail($id);
        return response()->json($appointment);
    }

    /**
     * Update an appointment (status / notes only — staff and time via admin panel).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled,no_show',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $appointment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully',
            'data'    => $appointment,
        ]);
    }

    /**
     * Cancel / delete an appointment.
     */
    public function destroy(int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully',
        ]);
    }

    /**
     * Get the authenticated customer's own appointments (V2).
     */
    public function myAppointments(Request $request): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();

        $appointments = Appointment::where('customer_id_new', $customer->id)
            ->with('staffNew:id,name')
            ->orderBy('starts_at', 'desc')
            ->get();

        return response()->json($appointments);
    }
}
