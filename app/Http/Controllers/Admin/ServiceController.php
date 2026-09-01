<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Models\Service;
use App\Models\Staff;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    private function ensureTenantAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('Admin Tenant'), 403);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Service::onlineBookable()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Service::findOrFail($id)]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $this->ensureTenantAdmin();

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
        $this->ensureTenantAdmin();

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
        $this->ensureTenantAdmin();

        try {
            Service::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => __('Service deleted.')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Compatibility endpoint for existing internal consumers. */
    public function timeSlots(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TimeSlot::active()->orderBy('start_time')->get(),
        ]);
    }

    public function storeTimeSlot(Request $request): JsonResponse
    {
        $this->ensureTenantAdmin();

        $data = $request->validate([
            'start_time' => ['required'],
            'end_time' => ['required', 'after:start_time'],
        ]);

        try {
            $slot = TimeSlot::create($data);
            return response()->json(['success' => true, 'message' => __('Time slot created.'), 'data' => $slot]);
        } catch (\Exception $e) {
            Log::error('storeTimeSlot: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleTimeSlot(Request $request, int $id): JsonResponse
    {
        $this->ensureTenantAdmin();

        try {
            TimeSlot::findOrFail($id)->update(['is_active' => $request->boolean('is_active')]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function destroyTimeSlot(int $id): JsonResponse
    {
        $this->ensureTenantAdmin();

        try {
            TimeSlot::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => __('Time slot deleted.')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /** Compatibility endpoint for existing internal consumers. */
    public function workingDays(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => WorkingDay::active()->orderBy('day_of_week')->get(),
        ]);
    }

    public function toggleWorkingDay(Request $request, int $id): JsonResponse
    {
        $this->ensureTenantAdmin();

        try {
            WorkingDay::findOrFail($id)->update(['is_active' => $request->boolean('is_active')]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function toggleStaffService(Request $request): JsonResponse
    {
        $this->ensureTenantAdmin();

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
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
