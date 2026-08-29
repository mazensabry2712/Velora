<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Domain\Booking\Services\SlotEngine;
use App\Models\Service;
use App\Models\Staff;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Service::onlineBookable()->orderBy('sort_order')->get()]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Service::findOrFail($id)]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        try {
            $service = Service::create($request->validated());
            return response()->json(['success' => true, 'message' => __('Service created.'), 'data' => $service]);
        } catch (\Exception $e) {
            Log::error('storeService: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(StoreServiceRequest $request, int $id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);
            $service->update($request->validated());
            return response()->json(['success' => true, 'message' => __('Service updated.'), 'data' => $service]);
        } catch (\Exception $e) {
            Log::error('updateService: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            Service::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => __('Service deleted.')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function timeSlots(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => TimeSlot::active()->orderBy('start_time')->get()]);
    }

    public function storeTimeSlot(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'start_time' => 'required',
                'end_time'   => 'required|after:start_time',
            ]);

            $slot = TimeSlot::create($data);
            return response()->json(['success' => true, 'message' => __('Time slot created.'), 'data' => $slot]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleTimeSlot(Request $request, int $id): JsonResponse
    {
        try {
            TimeSlot::findOrFail($id)->update(['is_active' => $request->boolean('is_active')]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function destroyTimeSlot(int $id): JsonResponse
    {
        try {
            TimeSlot::findOrFail($id)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function workingDays(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => WorkingDay::active()->orderBy('day_of_week')->get()]);
    }

    public function toggleWorkingDay(Request $request, int $id): JsonResponse
    {
        try {
            WorkingDay::findOrFail($id)->update(['is_active' => $request->boolean('is_active')]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Public booking availability.
     *
     * Staff IDs exposed to the browser are User IDs because the booking
     * submit contract uses the staff user's identifier. Internally we resolve
     * that identifier to the canonical Staff aggregate before asking SlotEngine
     * for availability.
     */
    public function availableTimeSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'       => ['required', 'date_format:Y-m-d'],
            'staff_id'   => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'timezone'   => ['nullable', 'timezone'],
        ]);

        $service = Service::query()
            ->whereKey((int) $validated['service_id'])
            ->where('is_active', true)
            ->where('is_online_bookable', true)
            ->first();

        $requestedStaffId = (int) $validated['staff_id'];
        $staff = Staff::query()
            ->where('user_id', $requestedStaffId)
            ->bookable()
            ->with(['workingHours', 'breaks', 'timeOff'])
            ->first();

        if (! $service || ! $staff || ! $staff->services()->whereKey($service->id)->exists()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'reason' => 'invalid_booking_selection',
            ]);
        }

        try {
            $timezone = $staff->timezone ?: config('app.timezone');
            $slots = app(SlotEngine::class)->getAvailableSlots(
                $service,
                $staff,
                Carbon::createFromFormat('Y-m-d', $validated['date'], $timezone),
                $timezone,
            );

            return response()->json([
                'success' => true,
                'timezone' => $timezone,
                'data' => $slots->map(fn ($slot) => [
                    'start_time' => $slot->startsAt->format('H:i'),
                    'end_time' => $slot->endsAt->format('H:i'),
                    'label' => $slot->startsAt->format('g:i A'),
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            Log::error('availableTimeSlots: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'data' => [],
                'message' => __('Unable to load availability.'),
            ], 500);
        }
    }

    public function toggleStaffService(Request $request): JsonResponse
    {
        try {
            $staff = Staff::where('user_id', (int) $request->staff_id)->firstOrFail();

            if ($request->boolean('attach')) {
                $staff->services()->syncWithoutDetaching([(int) $request->service_id]);
            } else {
                $staff->services()->detach((int) $request->service_id);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('toggleStaffService: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function staffServices(int $staffId): JsonResponse
    {
        $staff = Staff::with('services')->where('user_id', $staffId)->firstOrFail();

        return response()->json(['success' => true, 'data' => $staff->services]);
    }

    public function byService(int $serviceId): JsonResponse
    {
        $service = Service::query()
            ->whereKey($serviceId)
            ->where('is_active', true)
            ->where('is_online_bookable', true)
            ->firstOrFail();

        $staff = Staff::query()
            ->bookable()
            ->whereHas('services', fn ($query) => $query->whereKey($service->id))
            ->with('user:id,name,email')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Staff $staff) => [
                'id' => $staff->user_id,
                'staff_id' => $staff->id,
                'name' => $staff->full_name ?: $staff->user?->name,
                'email' => $staff->email ?: $staff->user?->email,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }
}
