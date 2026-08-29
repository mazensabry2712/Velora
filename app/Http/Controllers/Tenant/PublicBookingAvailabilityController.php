<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Domain\Booking\Services\SlotEngine;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PublicBookingAvailabilityController extends Controller
{
    public function services(): JsonResponse
    {
        $services = Service::query()
            ->onlineBookable()
            ->orderBy('sort_order')
            ->get([
                'id',
                'name',
                'name_ar',
                'name_i18n',
                'duration',
                'duration_minutes',
                'price',
                'description',
                'sort_order',
            ])
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->localized_name,
                'name_ar' => $service->name_ar,
                'duration' => $service->duration,
                'duration_minutes' => $service->duration_minutes ?: $service->duration,
                'price' => $service->price,
                'description' => $service->description,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function staffByService(int $serviceId): JsonResponse
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

    public function availableTimeSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'staff_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $service = Service::query()
            ->whereKey((int) $validated['service_id'])
            ->where('is_active', true)
            ->where('is_online_bookable', true)
            ->first();

        $staff = Staff::query()
            ->where('user_id', (int) $validated['staff_id'])
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
            Log::error('Public booking availability error: ' . $e->getMessage(), [
                'tenant_id' => tenant()?->getTenantKey(),
                'service_id' => $validated['service_id'],
                'staff_id' => $validated['staff_id'],
                'date' => $validated['date'],
            ]);

            return response()->json([
                'success' => false,
                'data' => [],
                'message' => __('Unable to load availability.'),
            ], 500);
        }
    }
}
