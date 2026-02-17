<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    /**
     * Store a newly created appointment
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255|regex:/^[\p{L}\p{N}\s\-\.]+$/u',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|string|max:20|regex:/^[\d\+\-\(\)\s]+$/',
                'appointment_date' => 'required|date|after_or_equal:today',
                'service_id' => 'nullable|exists:services,id',
                'staff_id' => 'nullable|exists:users,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Sanitize inputs
            $validated['customer_name'] = strip_tags(trim($validated['customer_name']));
            $validated['customer_phone'] = preg_replace('/[^\d\+\-\(\)\s]/', '', $validated['customer_phone']);
            if (!empty($validated['notes'])) {
                $validated['notes'] = strip_tags(trim($validated['notes']));
            }

            // Parse date and time from appointment_date
            $appointmentDateTime = \Carbon\Carbon::parse($validated['appointment_date']);
            $date = $appointmentDateTime->toDateString();
            $timeSlot = $appointmentDateTime->format('H:i');

            // Find or create customer
            $customerRole = Role::where('name', 'Customer')->first();

            $customer = User::where('email', $validated['customer_email'])->first();

            if (!$customer) {
                // Create new customer
                $customer = User::create([
                    'name' => $validated['customer_name'],
                    'email' => $validated['customer_email'],
                    'phone' => $validated['customer_phone'],
                    'password' => Hash::make(Str::random(16)),
                    'role_id' => $customerRole->id,
                ]);
            } else {
                // Update customer info if changed
                $customer->update([
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                ]);
            }

            // Create appointment
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'service_id' => $validated['service_id'] ?? null,
                'staff_id' => $validated['staff_id'] ?? null,
                'date' => $date,
                'time_slot' => $timeSlot,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Add to queue with formatted queue number
            $queue = Queue::create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber(),
                'status' => 'waiting',
                'is_vip' => $customer->is_vip ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'data' => [
                    'appointment' => $appointment,
                    'queue_number' => $queue->queue_number,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error booking appointment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the appointment'
            ], 500);
        }
    }



    /**
     * Display appointments (for authenticated users)
     */
    public function index(Request $request)
    {
        $appointments = Appointment::with(['customer', 'staff'])
            ->orderBy('appointment_date', 'desc')
            ->paginate(20);

        return response()->json($appointments);
    }

    /**
     * Show a specific appointment
     */
    public function show($id)
    {
        $appointment = Appointment::with(['customer', 'staff'])->findOrFail($id);
        return response()->json($appointment);
    }

    /**
     * Update appointment
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'appointment_date' => 'sometimes|date',
            'staff_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully',
            'data' => $appointment
        ]);
    }

    /**
     * Delete appointment
     */
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully'
        ]);
    }

    /**
     * Get customer's appointments
     */
    public function myAppointments(Request $request)
    {
        $user = $request->user();

        $appointments = Appointment::where('customer_id', $user->id)
            ->with('staff')
            ->orderBy('appointment_date', 'desc')
            ->get();

        return response()->json($appointments);
    }
}
