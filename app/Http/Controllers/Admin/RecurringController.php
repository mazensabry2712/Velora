<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\RecurringRule;
use App\Services\RecurringAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RecurringController — create and manage recurring appointment series.
 *
 * Routes (all under admin/api):
 *   POST  /recurring                         — create rule + seed + generate occurrences
 *   GET   /recurring/{ruleId}/appointments   — list all appointments in a series
 *   DELETE /recurring/{ruleId}              — cancel all future appointments in a series
 */
class RecurringController extends Controller
{
    public function __construct(
        private readonly RecurringAppointmentService $recurringService,
    ) {}

    /**
     * POST /admin/api/recurring
     *
     * Create a RecurringRule, book the first appointment, then batch-generate
     * all future occurrences (up to max_occurrences or ends_on).
     *
     * Body:
     * {
     *   "service_id":      1,
     *   "staff_id":        2,
     *   "starts_at":       "2026-04-01 10:00",
     *   "timezone":        "Asia/Riyadh",
     *   "customer_id":     5,
     *   "frequency":       "weekly",        // daily|weekly|monthly
     *   "interval":        1,               // every N units
     *   "days_of_week":    [1,3],           // Mon, Wed (0=Sun…6=Sat) — weekly only
     *   "ends_on":         "2026-06-30",    // optional cutoff date
     *   "max_occurrences": 10,              // optional cap
     *   "notes":           "…"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id'       => 'required|integer|exists:services,id',
            'staff_id'         => 'required|integer|exists:staff,id',
            'starts_at'        => 'required|date|after:now',
            'timezone'         => 'required|string|max:50',
            'customer_id'      => 'nullable|integer|exists:customers,id',
            'resource_id'      => 'nullable|integer|exists:resources,id',
            'attendees'        => 'nullable|integer|min:1',
            'notes'            => 'nullable|string|max:1000',
            'frequency'        => 'required|in:daily,weekly,monthly',
            'interval'         => 'required|integer|min:1|max:52',
            'days_of_week'     => 'nullable|array',
            'days_of_week.*'   => 'integer|between:0,6',
            'ends_on'          => 'nullable|date|after:starts_at',
            'max_occurrences'  => 'nullable|integer|min:2|max:365',
        ]);

        // 1. Create the RecurringRule
        $rule = RecurringRule::create([
            'frequency'        => $validated['frequency'],
            'interval'         => $validated['interval'],
            'days_of_week'     => $validated['days_of_week'] ?? null,
            'ends_on'          => $validated['ends_on']          ?? null,
            'max_occurrences'  => $validated['max_occurrences']  ?? null,
            'generated_count'  => 0,
        ]);

        // 2. Book the seed appointment using the booking service
        /** @var \App\Domain\Booking\Services\BookingCreationService $bookingService */
        $bookingService = app(\App\Domain\Booking\Services\BookingCreationService::class);

        $seed = $bookingService->create(new \App\Domain\Booking\DTOs\CreateBookingData(
            serviceId:   $validated['service_id'],
            staffId:     $validated['staff_id'],
            startsAt:    \Carbon\Carbon::parse($validated['starts_at'], $validated['timezone']),
            timezone:    $validated['timezone'],
            customerId:  $validated['customer_id'] ?? null,
            resourceId:  $validated['resource_id'] ?? null,
            attendees:   $validated['attendees']   ?? 1,
            source:      'admin',
            notes:       $validated['notes']       ?? null,
            recurringId: $rule->id,
        ));

        $rule->increment('generated_count');

        // 3. Generate all future occurrences
        $generated = $this->recurringService->generateFromSeed($seed);

        return response()->json([
            'success'          => true,
            'data'             => [
                'rule'              => $rule->fresh(),
                'seed_appointment'  => $seed,
                'generated_count'   => count($generated),
            ],
        ], 201);
    }

    /**
     * GET /admin/api/recurring/{ruleId}/appointments
     * List all appointments belonging to a recurring series.
     */
    public function appointments(int $ruleId): JsonResponse
    {
        $rule = RecurringRule::findOrFail($ruleId);

        $appointments = Appointment::where('recurring_id', $rule->id)
            ->with(['staffMember:id,name', 'service:id,name'])
            ->orderBy('starts_at')
            ->get();

        return response()->json(['success' => true, 'data' => $appointments]);
    }

    /**
     * DELETE /admin/api/recurring/{ruleId}
     * Cancel all FUTURE (pending/confirmed) appointments in the series.
     * Past or completed appointments are not touched.
     */
    public function cancelSeries(int $ruleId): JsonResponse
    {
        $rule = RecurringRule::findOrFail($ruleId);

        $cancelledCount = Appointment::where('recurring_id', $rule->id)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->where('starts_at', '>', now())
            ->update(['status' => Appointment::STATUS_CANCELLED]);

        return response()->json([
            'success' => true,
            'message' => __(":count upcoming appointment(s) cancelled.", ['count' => $cancelledCount]),
        ]);
    }
}
