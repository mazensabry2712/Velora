<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Booking\DTOs\CreateBookingData;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Booking\Services\BookingCreationService;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\Resource;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly BookingCreationService $bookingService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $tenantId = (string) tenant()->getTenantKey();
        $rateLimitKey = 'public-booking:' . $tenantId . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many booking attempts. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $validated = $request->validate([
                'customer_name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[\p{L}\p{N}\s\-\.]+$/u',
                ],
                'customer_email' => ['required', 'email', 'max:255'],
                'customer_phone' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^[\d\+\-\(\)\s]+$/',
                ],
                'appointment_date' => ['required', 'date', 'after_or_equal:today'],
                'appointment_time' => ['required', 'date_format:H:i'],
                'service_id' => [
                    'required',
                    Rule::exists('services', 'id')->where(fn ($q) => $q
                        ->where('is_active', true)
                        ->where('is_online_bookable', true)),
                ],
                'staff_id' => ['required', 'integer', Rule::exists('users', 'id')],
                'resource_id' => [
                    'nullable',
                    Rule::exists('resources', 'id')->where(fn ($q) => $q->where('is_active', true)),
                ],
                'timezone' => ['nullable', 'timezone'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $staffRecord = Staff::query()
                ->where('user_id', (int) $validated['staff_id'])
                ->where('is_active', true)
                ->where('accepts_bookings', true)
                ->first();

            if (! $staffRecord) {
                throw Validator::make([], [])->after(function ($validator) {
                    $validator->errors()->add('staff_id', 'The selected staff member is not available for booking.');
                })->validate();
            }

            if (! $staffRecord->services()
                ->where('services.id', (int) $validated['service_id'])
                ->exists()) {
                throw Validator::make([], [])->after(function ($validator) {
                    $validator->errors()->add('staff_id', 'The selected staff member cannot provide this service.');
                })->validate();
            }

            if (! empty($validated['resource_id'])) {
                $resourceValid = Resource::query()
                    ->whereKey((int) $validated['resource_id'])
                    ->where('is_active', true)
                    ->whereHas('services', fn ($q) => $q->whereKey((int) $validated['service_id']))
                    ->exists();

                if (! $resourceValid) {
                    throw Validator::make([], [])->after(function ($validator) {
                        $validator->errors()->add('resource_id', 'The selected resource is not available for this service.');
                    })->validate();
                }
            }

            $validated['customer_name'] = strip_tags(trim($validated['customer_name']));
            $validated['customer_phone'] = preg_replace('/[^\d\+\-\(\)\s]/', '', $validated['customer_phone']);

            if (! empty($validated['notes'])) {
                $validated['notes'] = strip_tags(trim($validated['notes']));
            }

            // The appointment date/time represents the business schedule. Use the
            // staff member's timezone as the authoritative timezone rather than
            // trusting the customer's device timezone.
            $tz = $staffRecord->timezone ?: config('app.timezone');
            $startsAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $validated['appointment_date'] . ' ' . $validated['appointment_time'],
                $tz
            );

            [$appointment, $queue, $customer] = DB::transaction(function () use ($validated, $tz, $startsAt, $staffRecord) {
                [$firstName, $lastName] = $this->splitName($validated['customer_name']);

                $customer = Customer::firstOrNew(['email' => $validated['customer_email']]);

                // A blocked customer must not be able to create new public bookings.
                if ($customer->exists && $customer->is_blocked) {
                    throw Validator::make([], [])->after(function ($validator) {
                        $validator->errors()->add('customer_email', 'This customer is not allowed to book appointments.');
                    })->validate();
                }

                $customer->fill([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $validated['customer_phone'],
                    'acquisition_source' => $customer->exists ? $customer->acquisition_source : 'online',
                ]);
                $customer->save();

                $data = new CreateBookingData(
                    serviceId: (int) $validated['service_id'],
                    staffId: $staffRecord->id,
                    startsAt: $startsAt,
                    timezone: $tz,
                    customerId: $customer->id,
                    resourceId: isset($validated['resource_id']) ? (int) $validated['resource_id'] : null,
                    source: 'online',
                    notes: $validated['notes'] ?? null,
                );

                $appointment = $this->bookingService->create($data);

                $queue = Queue::create([
                    'appointment_id' => $appointment->id,
                    'queue_number' => Queue::generateQueueNumber($startsAt),
                    'queue_date' => $startsAt->toDateString(),
                    'status' => 'waiting',
                    'is_vip' => false,
                    'notes' => $validated['notes'] ?? null,
                ]);

                return [$appointment, $queue, $customer];
            });

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'data' => [
                    'appointment' => $appointment,
                    'queue_number' => $queue->queue_number,
                    'queue' => $queue,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->first_name . ' ' . $customer->last_name,
                        'email' => $customer->email,
                    ],
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (SlotUnavailableException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slot not available',
                'reason' => $e->getMessage(),
            ], 409);
        } catch (\Exception $e) {
            Log::error('Public booking error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the appointment',
            ], 500);
        }
    }

    private function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::with(['customerNew:id,first_name,last_name,email', 'staffNew:id,name'])
            ->orderBy('starts_at', 'desc')
            ->paginate(20);

        return response()->json($appointments);
    }

    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['customerNew', 'staffNew'])->findOrFail($id);
        return response()->json($appointment);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled,no_show',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully',
            'data' => $appointment,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully',
        ]);
    }

    public function myAppointments(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::query()->where('email', $user->email)->first();

        if (! $customer) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $appointments = Appointment::query()
            ->where('customer_id_new', $customer->id)
            ->with('staffNew:id,name')
            ->orderByDesc('starts_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
        ]);
    }
}
