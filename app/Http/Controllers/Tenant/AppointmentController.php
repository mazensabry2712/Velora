<?php

namespace App\Http\Controllers\Tenant;

use App\Application\Booking\Actions\DeleteAppointment;
use App\Application\Booking\Actions\UpdateAppointment;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly UpdateAppointment $updateAppointment,
        private readonly DeleteAppointment $deleteAppointment,
    ) {}

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
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled,no_show',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment = $this->updateAppointment->execute($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully',
            'data' => $appointment,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteAppointment->execute($id);

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
