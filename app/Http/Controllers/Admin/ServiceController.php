<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Models\Service;
use App\Models\StaffSchedule;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\WorkingDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    // ── Services CRUD ────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Service::active()->get()]);
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

    // ── Time Slots ───────────────────────────────────────────────────────

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

    // ── Working Days ─────────────────────────────────────────────────────

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

    // ── Available Slots (Booking Form) ───────────────────────────────────

    public function availableTimeSlots(Request $request): JsonResponse
    {
        try {
            $date    = $request->input('date');
            $staffId = $request->input('staff_id');
            $exclude = $request->input('exclude_appointment_id');

            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                return response()->json(['success' => true, 'data' => [], 'reason' => 'past_date']);
            }

            $allSlots    = TimeSlot::active()->orderBy('start_time')->get();
            $dayOfWeek   = date('w', strtotime($date));
            $staffSchedule = StaffSchedule::where('user_id', $staffId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (!$staffSchedule) {
                return response()->json(['success' => true, 'data' => [], 'reason' => 'staff_not_working']);
            }

            $bookedSlots = \App\Models\Appointment::where('date', $date)
                ->where('staff_id', $staffId)
                ->whereNotIn('status', ['cancelled'])
                ->when($exclude, fn ($q) => $q->where('id', '!=', $exclude))
                ->pluck('time_slot')
                ->toArray();

            $available = $allSlots->filter(function ($slot) use ($bookedSlots, $staffSchedule) {
                if (in_array($slot->start_time, $bookedSlots)) {
                    return false;
                }
                return $slot->start_time >= $staffSchedule->start_time
                    && $slot->start_time < $staffSchedule->end_time;
            })->values();

            return response()->json(['success' => true, 'data' => $available]);
        } catch (\Exception $e) {
            Log::error('availableTimeSlots: ' . $e->getMessage());
            return response()->json(['success' => false, 'data' => [], 'message' => $e->getMessage()], 500);
        }
    }

    // ── Staff-Service Assignment ─────────────────────────────────────────

    public function toggleStaffService(Request $request): JsonResponse
    {
        try {
            $user = User::findOrFail($request->staff_id);

            if ($request->boolean('attach')) {
                $user->services()->syncWithoutDetaching([$request->service_id]);
            } else {
                $user->services()->detach($request->service_id);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('toggleStaffService: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function staffServices(int $staffId): JsonResponse
    {
        $user = User::with('services')->findOrFail($staffId);
        return response()->json(['success' => true, 'data' => $user->services]);
    }
}
