<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Role;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use App\Models\StaffSchedule;
use App\Models\Notification;
use App\Models\Queue;

class AdminController extends Controller
{
    /**
     * Show login page
     */
    public function login()
    {
        return view('auth.login');
    }

    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        // Current week and last week dates
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        // Total appointments (all time)
        $totalAppointments = Appointment::count();
        $lastWeekAppointments = Appointment::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();
        $thisWeekAppointments = Appointment::whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();

        // Calculate percentage change
        if ($lastWeekAppointments > 0) {
            $appointmentsChange = round((($thisWeekAppointments - $lastWeekAppointments) / $lastWeekAppointments) * 100);
        } else {
            $appointmentsChange = $thisWeekAppointments > 0 ? 100 : 0;
        }

        // Confirmed appointments for today
        $confirmedToday = Appointment::where('status', 'confirmed')->whereDate('date', today())->count();

        // Queue count
        $queueCount = \App\Models\Queue::whereIn('status', ['waiting', 'serving'])->count();

        // Total customers
        $totalCustomers = User::whereHas('role', function ($q) {
            $q->where('name', 'Customer');
        })->count();

        // New customers this week
        $newCustomersThisWeek = User::whereHas('role', function ($q) {
            $q->where('name', 'Customer');
        })->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();

        // Statistics
        $stats = [
            'total_appointments' => $totalAppointments,
            'appointments_change' => $appointmentsChange,
            'confirmed' => $confirmedToday,
            'queue' => $queueCount,
            'customers' => $totalCustomers,
            'new_customers_this_week' => $newCustomersThisWeek,
        ];

        // Today's appointments
        $todayAppointments = Appointment::with(['customer', 'staff', 'service'])
            ->whereDate('date', today())
            ->orderBy('time_slot')
            ->get();

        // Current queue
        $currentQueue = \App\Models\Queue::with(['appointment.customer'])
            ->whereIn('status', ['waiting', 'serving'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.dashboard.index', compact('stats', 'todayAppointments', 'currentQueue'));
    }

    /**
     * Show appointments page
     */
    public function appointments(Request $request)
    {
        // Build query with filters
        $query = Appointment::with(['customer', 'staff', 'service', 'queue']);

        // Date filter
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('date', today());
                    break;
                case 'week':
                    $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
                    break;
                case 'custom':
                    if ($request->filled('date_from')) {
                        $query->whereDate('date', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $query->whereDate('date', '<=', $request->date_to);
                    }
                    break;
            }
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Staff filter
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Service name filter (from Services table - Nutrition, beauty, etc.)
        if ($request->filled('service_name')) {
            $query->where('service_type', 'like', '%' . $request->service_name . '%');
        }

        // Service type filter (consultation, examination, follow-up, etc.)
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Queue filter
        if ($request->filled('queue_status')) {
            switch ($request->queue_status) {
                case 'in_queue':
                    $query->whereHas('queue');
                    break;
                case 'not_in_queue':
                    $query->whereDoesntHave('queue');
                    break;
                case 'waiting':
                    $query->whereHas('queue', function($q) {
                        $q->where('status', 'waiting');
                    });
                    break;
                case 'serving':
                    $query->whereHas('queue', function($q) {
                        $q->where('status', 'serving');
                    });
                    break;
                case 'queue_completed':
                    $query->whereHas('queue', function($q) {
                        $q->where('status', 'completed');
                    });
                    break;
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'date');
        $sortDir = $request->get('dir', 'desc');

        if ($sortBy === 'customer') {
            $query->join('users', 'appointments.customer_id', '=', 'users.id')
                  ->orderBy('users.name', $sortDir)
                  ->orderBy('appointments.date', 'desc')
                  ->orderBy('appointments.time_slot', 'asc')
                  ->orderBy('appointments.id', 'asc')
                  ->select('appointments.*');
        } else {
            $query->orderBy($sortBy, $sortDir)
                  ->orderBy('time_slot', 'asc')
                  ->orderBy('id', 'asc'); // Consistent ordering
        }

        // Clone query for grouped view (get all filtered appointments, not just current page)
        $allFilteredAppointments = (clone $query)
            ->orderBy('date', 'desc')
            ->orderBy('time_slot', 'asc')
            ->get();

        // Group appointments by date for accordion view
        $appointmentsByDate = $allFilteredAppointments
            ->groupBy(function($appointment) {
                return $appointment->date->format('Y-m-d');
            })
            ->map(function($dayAppointments, $date) {
                $dateCarbon = \Carbon\Carbon::parse($date);
                $total = $dayAppointments->count();
                $confirmed = $dayAppointments->where('status', 'confirmed')->count();
                $pending = $dayAppointments->where('status', 'pending')->count();
                $completed = $dayAppointments->where('status', 'completed')->count();
                $cancelled = $dayAppointments->where('status', 'cancelled')->count();

                return [
                    'date' => $date,
                    'date_formatted' => $dateCarbon->translatedFormat('l, F j, Y'),
                    'date_short' => $dateCarbon->format('M j'),
                    'is_today' => $dateCarbon->isToday(),
                    'is_tomorrow' => $dateCarbon->isTomorrow(),
                    'is_past' => $dateCarbon->isPast() && !$dateCarbon->isToday(),
                    'diff_humans' => $dateCarbon->diffForHumans(),
                    'appointments' => $dayAppointments->sortBy('time_slot')->values(),
                    'appointment_ids' => $dayAppointments->pluck('id')->toArray(),
                    'total' => $total,
                    'confirmed' => $confirmed,
                    'pending' => $pending,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    // Percentages
                    'confirmed_percent' => $total > 0 ? round(($confirmed / $total) * 100, 1) : 0,
                    'pending_percent' => $total > 0 ? round(($pending / $total) * 100, 1) : 0,
                    'completed_percent' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                    'cancelled_percent' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
                    'progress_percent' => $total > 0 ? round((($completed + $cancelled) / $total) * 100) : 0,
                    'revenue' => $dayAppointments->where('status', '!=', 'cancelled')->sum(function($apt) {
                        return $apt->service?->price ?? 0;
                    }),
                ];
            })
            ->sortKeysDesc() // Sort dates descending (newest first)
            ->values(); // Reset keys

        // Active filters for display
        $activeFilters = [];
        if ($request->filled('date_filter') && $request->date_filter !== 'all') {
            $activeFilters['date'] = $request->date_filter;
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $activeFilters['status'] = $request->status;
        }
        if ($request->filled('staff_id')) {
            $activeFilters['staff'] = User::find($request->staff_id)?->name;
        }
        if ($request->filled('service_name')) {
            $activeFilters['service'] = $request->service_name;
        }

        // Paginate
        $perPage = $request->get('per_page', 15);
        // Ensure per_page is within allowed range
        $perPage = in_array($perPage, [5, 10, 15, 50, 75, 100]) ? $perPage : 15;
        $appointments = $query->paginate($perPage)->withQueryString();

        // Enhanced Statistics
        $todayAppointments = Appointment::whereDate('date', today());
        $thisWeekAppointments = Appointment::whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        $thisMonthAppointments = Appointment::whereMonth('date', now()->month)->whereYear('date', now()->year);

        // No-show rate (completed + cancelled vs total past appointments)
        $pastAppointments = Appointment::where('date', '<', today())->count();
        $completedPast = Appointment::where('date', '<', today())->where('status', 'completed')->count();
        $noShowRate = $pastAppointments > 0 ? round((($pastAppointments - $completedPast) / $pastAppointments) * 100, 1) : 0;

        // Average daily appointments (this month)
        $thisMonthCount = $thisMonthAppointments->count();
        $daysInMonth = now()->day; // Days passed in current month
        $avgDaily = $daysInMonth > 0 ? round($thisMonthCount / $daysInMonth, 1) : 0;

        // Top services
        $topServices = Appointment::select('service_type', DB::raw('count(*) as total'))
            ->whereNotNull('service_type')
            ->groupBy('service_type')
            ->orderBy('total', 'desc')
            ->limit(3)
            ->get();

        $stats = [
            'today' => $todayAppointments->count(),
            'today_confirmed' => $todayAppointments->where('status', 'confirmed')->count(),
            'pending' => Appointment::where('status', 'pending')->count(),
            'this_week' => $thisWeekAppointments->count(),
            'cancelled_month' => Appointment::where('status', 'cancelled')
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->count(),
            'in_queue' => Appointment::whereHas('queue', function($q) {
                $q->whereIn('status', ['waiting', 'serving']);
            })->count(),
            'no_show_rate' => $noShowRate,
            'avg_daily' => $avgDaily,
            'top_services' => $topServices,
        ];

        // Get services from Services table (Nutrition, beauty, etc.)
        $services = Service::where('is_active', true)->get();

        // Get distinct service types from appointments (استشارة، كشف، متابعة، etc.)
        $serviceTypes = Appointment::whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->distinct()
            ->pluck('service_type');

        // Get staff for filters
        $staffRole = Role::whereIn('name', ['Staff', 'Admin Tenant'])->pluck('id');
        $staffMembers = User::whereIn('role_id', $staffRole)->get();

        return view('admin.appointments.index', compact('appointments', 'appointmentsByDate', 'stats', 'services', 'serviceTypes', 'staffMembers', 'activeFilters'));
    }

    /**
     * Show queue days listing page
     */
    public function queueDays()
    {
        // Get all days that have queue entries, grouped by date
        $days = \App\Models\Queue::selectRaw("
                DATE(created_at) as date,
                MAX(created_at) as last_activity,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'serving' THEN 1 ELSE 0 END) as serving,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN is_vip = 1 THEN 1 ELSE 0 END) as vip
            ")
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) DESC')
            ->get();

        // Add today if not exists (so "New Day" button makes sense)
        $today = now()->toDateString();
        $todayExists = $days->first(fn($day) => $day->date === $today);

        if (!$todayExists) {
            $days->prepend((object)[
                'date' => $today,
                'last_activity' => null,
                'total' => 0,
                'waiting' => 0,
                'serving' => 0,
                'completed' => 0,
                'vip' => 0,
            ]);
        }

        // Overall stats for all days
        $overallStats = [
            'waiting' => \App\Models\Queue::where('status', 'waiting')->count(),
            'serving' => \App\Models\Queue::where('status', 'serving')->count(),
            'completed' => \App\Models\Queue::where('status', 'completed')->count(),
        ];

        return view('admin.queue.days', compact('days', 'overallStats'));
    }

    /**
     * Show queue management page for a specific day
     */
    public function queue($date = null)
    {
        // If no date provided, default to today
        $date = $date ?? now()->toDateString();

        $queues = \App\Models\Queue::with(['appointment.customer', 'appointment.staff', 'appointment.service'])
            ->whereDate('created_at', $date)
            ->orderBy('is_vip', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.queue.index', compact('queues', 'date'));
    }

    /**
     * Print queue page
     */
    public function printQueue($date = null)
    {
        $date = $date ?? now()->toDateString();

        $queues = \App\Models\Queue::with(['appointment.customer', 'appointment.staff', 'appointment.service'])
            ->whereDate('created_at', $date)
            ->orderBy('is_vip', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.queue.print', compact('queues', 'date'));
    }

    /**
     * Export queue to Excel
     */
    public function exportQueueToExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\QueuesExport,
            'queue_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Show reports page
     */
    public function reports()
    {
        // General Statistics
        $stats = [
            'total_appointments' => Appointment::count(),
            'confirmed_appointments' => Appointment::where('status', 'confirmed')->count(),
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
            'total_customers' => User::whereHas('role', function($q) {
                $q->where('name', 'Customer');
            })->count(),
        ];

        // Appointments by status
        $appointmentsByStatus = Appointment::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Queue statistics
        $queueStats = [
            'waiting' => \App\Models\Queue::where('status', 'waiting')->count(),
            'serving' => \App\Models\Queue::where('status', 'serving')->count(),
            'completed' => \App\Models\Queue::where('status', 'completed')->count(),
            'priority' => \App\Models\Queue::where('is_vip', true)->whereIn('status', ['waiting', 'serving'])->count(),
        ];

        // Staff performance
        $staffPerformance = User::whereHas('role', function($q) {
                $q->whereIn('name', ['Admin Tenant', 'Staff']);
            })
            ->withCount(['staffAppointments' => function($q) {
                $q->where('status', 'confirmed');
            }])
            ->having('staff_appointments_count', '>', 0)
            ->orderBy('staff_appointments_count', 'desc')
            ->get();

        // Service types
        $serviceTypes = Appointment::whereNotNull('service_type')
            ->select('service_type', DB::raw('count(*) as count'))
            ->groupBy('service_type')
            ->orderBy('count', 'desc')
            ->get();

        return view('admin.reports.index', compact('stats', 'appointmentsByStatus', 'queueStats', 'staffPerformance', 'serviceTypes'));
    }

    /**
     * Store new appointment (AJAX)
     */
    public function storeAppointment(Request $request)
    {
        try {
            Log::info('=== STORE APPOINTMENT STARTED ===', [
                'request_data' => $request->all()
            ]);

            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email',
                'appointment_date' => 'required|date|after_or_equal:today',
                'appointment_time' => 'required',
                'staff_id' => 'nullable|exists:users,id',
                'service_id' => 'nullable|exists:services,id',
                'service_type' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'add_to_queue' => 'nullable|boolean',
                'queue_date' => 'nullable|date|after_or_equal:today',
            ]);

            Log::info('Validation passed', ['validated' => $validated]);

            // Get or create customer
            $customerRole = Role::where('name', 'Customer')->first();

            $customer = User::firstOrCreate(
                ['email' => $validated['customer_email'] ?? $validated['customer_phone'] . '@temp.local'],
                [
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'password' => bcrypt('password123'),
                    'role_id' => $customerRole?->id,
                ]
            );

            // Update name and phone if customer exists
            $customer->update([
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
            ]);

            Log::info('Customer created/updated', ['customer_id' => $customer->id]);

            // Create appointment
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'staff_id' => $validated['staff_id'] ?? auth()->id(),
                'service_id' => $validated['service_id'] ?? null,
                'date' => $validated['appointment_date'],
                'time_slot' => $validated['appointment_time'],
                'status' => $request->add_to_queue ? 'confirmed' : 'pending',
                'service_type' => $validated['service_type'],
                'notes' => $validated['notes'],
            ]);

            Log::info('Appointment created', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'staff_id' => $appointment->staff_id,
                'date' => $appointment->date,
                'time_slot' => $appointment->time_slot,
                'status' => $appointment->status
            ]);

            // Add to queue if requested
            if ($request->add_to_queue) {
                $queueDate = $validated['queue_date'] ?? $validated['appointment_date'];

                $isVip = $customer->is_vip ?? false;

                $queue = Queue::create([
                    'appointment_id' => $appointment->id,
                    'queue_number' => Queue::generateQueueNumber(),
                    'queue_date' => $queueDate,
                    'status' => 'waiting',
                    'is_vip' => $isVip,
                ]);

                Log::info('Queue created', [
                    'queue_id' => $queue->id,
                    'appointment_id' => $appointment->id,
                    'queue_number' => $queue->queue_number
                ]);
            }

            Log::info('=== STORE APPOINTMENT SUCCESS ===', [
                'appointment_id' => $appointment->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الحجز بنجاح' . ($request->add_to_queue ? ' والإضافة للطابور' : ''),
                'data' => $appointment->fresh(['queue', 'customer', 'staff', 'service'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in storeAppointment', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing appointment', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحفظ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appointment details (AJAX)
     */
    public function showAppointment($id)
    {
        try {
            $appointment = Appointment::with(['customer', 'staff'])->findOrFail($id);

            $statusMap = [
                'pending' => 'قيد الانتظار',
                'confirmed' => 'مؤكد',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغي',
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                    'customer_name' => $appointment->customer?->name ?? 'غير محدد',
                    'customer_phone' => $appointment->customer?->phone ?? '-',
                    'customer_email' => $appointment->customer?->email ?? '-',
                    'date' => $appointment->date->format('Y-m-d'),
                    'time_slot' => $appointment->time_slot,
                    'service_type' => $appointment->service_type,
                    'notes' => $appointment->notes,
                    'status' => $appointment->status,
                    'status_ar' => $statusMap[$appointment->status] ?? $appointment->status,
                    'staff_name' => $appointment->staff?->name ?? '-',
                    'staff_id' => $appointment->staff_id,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching appointment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل البيانات'
            ], 500);
        }
    }

    /**
     * Update appointment (AJAX)
     */
    public function updateAppointment(Request $request, $id)
    {
        try {
            $appointment = Appointment::findOrFail($id);

            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email',
                'appointment_date' => 'required|date',
                'appointment_time' => 'required',
                'service_id' => 'nullable|exists:services,id',
                'service_type' => 'nullable|string|max:255',
                'staff_id' => 'nullable|exists:users,id',
                'status' => 'required|in:pending,confirmed,completed,cancelled',
                'notes' => 'nullable|string',
            ]);

            // Check if date/time changed and appointment has queue
            $dateChanged = $appointment->date->format('Y-m-d') !== $validated['appointment_date'];
            $timeChanged = $appointment->time_slot !== $validated['appointment_time'];

            if (($dateChanged || $timeChanged) && $appointment->queue) {
                // If appointment moved to past date, remove from queue
                if (\Carbon\Carbon::parse($validated['appointment_date']) < today()) {
                    $appointment->queue->delete();
                }
            }

            // Update customer info
            if ($appointment->customer) {
                $appointment->customer->update([
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'email' => $validated['customer_email'] ?? $appointment->customer->email,
                ]);
            }

            // Update appointment
            $appointment->update([
                'staff_id' => $validated['staff_id'] ?? $appointment->staff_id,
                'service_id' => $validated['service_id'] ?? $appointment->service_id,
                'date' => $validated['appointment_date'],
                'time_slot' => $validated['appointment_time'],
                'service_type' => $validated['service_type'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
            ]);

            // Reload relationships to get updated queue status
            return response()->json([
                'success' => true,
                'message' => 'تم تعديل الحجز بنجاح',
                'data' => $appointment->fresh(['customer', 'staff', 'queue'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating appointment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التعديل'
            ], 500);
        }
    }

    /**
     * Quick status update (AJAX) - for changing status from dropdown
     */
    public function quickStatusUpdate(Request $request, $id)
    {
        try {
            $appointment = Appointment::findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|in:pending,confirmed,cancelled,completed'
            ]);

            $appointment->update(['status' => $validated['status']]);

            // Update queue status if appointment has a queue entry
            if ($appointment->queue) {
                // Sync queue status with appointment status
                if ($validated['status'] === 'cancelled') {
                    // When appointment is cancelled, mark queue as skipped
                    $appointment->queue->update(['status' => 'skipped']);
                } elseif ($validated['status'] === 'completed') {
                    // When appointment is completed, mark queue as completed
                    $appointment->queue->update(['status' => 'completed']);
                } elseif ($validated['status'] === 'confirmed') {
                    // When appointment is confirmed, ensure queue is waiting (if not already serving)
                    if ($appointment->queue->status !== 'serving') {
                        $appointment->queue->update(['status' => 'waiting']);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => app()->getLocale() === 'ar' ? 'تم تحديث الحالة بنجاح' : 'Status updated successfully',
                'data' => $appointment->load('queue')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar' ? 'خطأ في البيانات' : 'Invalid data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating appointment status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar' ? 'حدث خطأ أثناء التحديث' : 'Error updating status'
            ], 500);
        }
    }

    /**
     * Delete appointment (AJAX)
     */
    public function destroyAppointment($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحجز بنجاح'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting appointment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف'
            ], 500);
        }
    }

    /**
     * Add appointment to queue
     */
    public function addAppointmentToQueue(Request $request, $id)
    {
        try {
            $appointment = Appointment::findOrFail($id);

            // Check if appointment already in queue
            if ($appointment->queue) {
                return response()->json([
                    'success' => false,
                    'message' => app()->getLocale() === 'ar' ? 'الموعد مضاف للطابور بالفعل' : 'Appointment already in queue'
                ], 400);
            }

            // Validate appointment can be added to queue
            if (!$appointment->canBeAddedToQueue()) {
                return response()->json([
                    'success' => false,
                    'message' => app()->getLocale() === 'ar' ? 'لا يمكن إضافة هذا الموعد للطابور' : 'Cannot add this appointment to queue'
                ], 400);
            }

            // Check if appointment date is in the past
            if ($appointment->date < today()) {
                return response()->json([
                    'success' => false,
                    'message' => app()->getLocale() === 'ar' ? 'لا يمكن إضافة موعد منتهي للطابور' : 'Cannot add past appointment to queue'
                ], 400);
            }

            // Validate queue date if provided
            $validated = $request->validate([
                'queue_date' => 'nullable|date|after_or_equal:today',
            ]);

            $queueDate = $validated['queue_date'] ?? $appointment->date->format('Y-m-d');

            // Check if customer is VIP
            $isVip = $appointment->customer->is_vip ?? false;

            // Create queue entry
            $queue = \App\Models\Queue::create([
                'appointment_id' => $appointment->id,
                'queue_number' => \App\Models\Queue::generateQueueNumber(),
                'queue_date' => $queueDate,
                'status' => 'waiting',
                'is_vip' => $isVip,
            ]);

            // Auto-confirm appointment when added to queue
            if ($appointment->status === 'pending') {
                $appointment->update(['status' => 'confirmed']);
            }

            return response()->json([
                'success' => true,
                'message' => app()->getLocale() === 'ar' ? 'تمت إضافة الموعد للطابور بنجاح' : 'Added to queue successfully',
                'data' => [
                    'queue' => $queue,
                    'appointment' => $appointment->fresh('queue')
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error adding to queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar' ? 'حدث خطأ أثناء الإضافة للطابور' : 'Error adding to queue'
            ], 500);
        }
    }

    /**
     * Remove appointment from queue
     */
    public function removeFromQueue($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);

            // Check if appointment has a queue entry
            if (!$appointment->queue) {
                return response()->json([
                    'success' => false,
                    'message' => app()->getLocale() === 'ar' ? 'الموعد غير مضاف للطابور' : 'Appointment not in queue'
                ], 400);
            }

            $queueStatus = $appointment->queue->status;

            // Delete queue entry
            $appointment->queue->delete();

            // Update appointment status based on queue status when removed
            if ($queueStatus === 'completed') {
                // If queue was completed, mark appointment as completed
                $appointment->update(['status' => 'completed']);
            } elseif (in_array($queueStatus, ['cancelled', 'skipped'])) {
                // If queue was cancelled/skipped, mark appointment as cancelled
                $appointment->update(['status' => 'cancelled']);
            }
            // If queue was waiting/serving, keep appointment as is (confirmed)

            return response()->json([
                'success' => true,
                'message' => app()->getLocale() === 'ar' ? 'تمت إزالة الموعد من الطابور بنجاح' : 'Removed from queue successfully',
                'data' => $appointment->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error removing from queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar' ? 'حدث خطأ أثناء الإزالة من الطابور' : 'Error removing from queue'
            ], 500);
        }
    }

    /**
     * Send appointment reminder to customer
     */
    public function sendReminder($id)
    {
        try {
            $appointment = Appointment::with(['customer', 'service', 'staff'])->findOrFail($id);

            // Validate appointment status and date
            if ($appointment->status === 'cancelled' || $appointment->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => app()->getLocale() === 'ar'
                        ? 'لا يمكن إرسال تذكير للموعد الملغي أو المكتمل'
                        : 'Cannot send reminder for cancelled or completed appointment'
                ], 400);
            }

            if ($appointment->date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => app()->getLocale() === 'ar'
                        ? 'لا يمكن إرسال تذكير لموعد قد انتهى'
                        : 'Cannot send reminder for past appointment'
                ], 400);
            }

            // Send email reminder
            \Mail::to($appointment->customer->email)->send(
                new \App\Mail\AppointmentReminderMail(
                    $appointment,
                    $appointment->customer,
                    app()->getLocale()
                )
            );

            // Log the notification
            Notification::create([
                'user_id' => $appointment->customer_id,
                'appointment_id' => $appointment->id,
                'type' => 'reminder',
                'message' => app()->getLocale() === 'ar'
                    ? 'تذكير: لديك موعد في ' . \Carbon\Carbon::parse($appointment->date)->format('Y-m-d') . ' الساعة ' . $appointment->time_slot
                    : 'Reminder: You have an appointment on ' . \Carbon\Carbon::parse($appointment->date)->format('Y-m-d') . ' at ' . $appointment->time_slot,
                'is_read' => false,
            ]);

            \Log::info('Appointment reminder sent', [
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->customer_id,
                'sent_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => app()->getLocale() === 'ar'
                    ? 'تم إرسال التذكير بنجاح'
                    : 'Reminder sent successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'customer' => $appointment->customer->name,
                    'date' => $appointment->date->format('Y-m-d'),
                    'time' => $appointment->time_slot
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error sending appointment reminder: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'حدث خطأ أثناء إرسال التذكير'
                    : 'Error sending reminder'
            ], 500);
        }
    }

    /**
     * Export appointments to Excel
     */
    public function exportAppointmentsExcel(Request $request)
    {
        $period = $request->get('period', $request->get('date_filter', 'month'));
        $startDate = $request->get('start_date', $request->get('date_from'));
        $endDate = $request->get('end_date', $request->get('date_to'));

        $tenant = tenant();
        $fileName = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AppointmentsExport($tenant, $period, $startDate, $endDate),
            $fileName
        );
    }

    /**
     * Generate QR Code for appointment
     */
    public function generateQRCode($id)
    {
        $appointment = Appointment::with(['customer', 'staff', 'service'])->findOrFail($id);

        $qrData = "Appointment #" . $appointment->id . "\n";
        $qrData .= "Customer: " . ($appointment->customer?->name ?? 'N/A') . "\n";
        $qrData .= "Phone: " . ($appointment->customer?->phone ?? 'N/A') . "\n";
        $qrData .= "Service: " . ($appointment->service?->name ?? $appointment->service_type) . "\n";
        $qrData .= "Staff: " . ($appointment->staff?->name ?? 'N/A') . "\n";
        $qrData .= "Date: " . $appointment->date->format('Y-m-d') . "\n";
        $qrData .= "Time: " . $appointment->time_slot . "\n";
        $qrData .= "Status: " . $appointment->status;

        $qrCode = \SimpleSoftwareIO\SimpleQrCode\Facades\QrCode::size(300)
            ->format('png')
            ->generate($qrData);

        return response($qrCode)->header('Content-Type', 'image/png');
    }

    /**
     * Bulk action on day's appointments
     */
    public function bulkDayAction(Request $request)
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:confirm_all,complete_all',
                'appointment_ids' => 'required|array',
                'appointment_ids.*' => 'exists:appointments,id'
            ]);

            $action = $validated['action'];
            $ids = $validated['appointment_ids'];
            $updated = 0;

            if ($action === 'confirm_all') {
                $updated = Appointment::whereIn('id', $ids)
                    ->where('status', 'pending')
                    ->update(['status' => 'confirmed']);
            } elseif ($action === 'complete_all') {
                $updated = Appointment::whereIn('id', $ids)
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->update(['status' => 'completed']);
            }

            return response()->json([
                'success' => true,
                'message' => $updated . ' appointments updated successfully',
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add customer to queue (AJAX)
     */
    public function addToQueue(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email',
                'staff_id' => 'required|exists:users,id',
                'service_id' => 'required|exists:services,id',
                'is_priority' => 'nullable|boolean',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Get or create customer
            $customerRole = Role::where('name', 'Customer')->first();

            $customer = User::firstOrCreate(
                ['email' => $validated['customer_email'] ?? $validated['customer_phone'] . '@temp.local'],
                [
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                    'role_id' => $customerRole?->id,
                ]
            );

            // Update name and phone if customer exists
            $customer->update([
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
            ]);

            // Get service info
            $service = Service::find($validated['service_id']);

            // Create appointment for today
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'staff_id' => $validated['staff_id'],
                'service_id' => $validated['service_id'],
                'date' => now()->toDateString(),
                'time_slot' => now()->format('H:i'),
                'status' => 'pending',
                'service_type' => $service?->name,
            ]);

            // Add to queue with formatted queue number
            $queue = \App\Models\Queue::create([
                'appointment_id' => $appointment->id,
                'queue_number' => \App\Models\Queue::generateQueueNumber(),
                'status' => 'waiting',
                'is_vip' => $validated['is_priority'] ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة العميل إلى قائمة الانتظار - رقم الدور: #' . $queue->queue_number,
                'data' => $queue->load(['appointment.customer', 'appointment.staff'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error adding to queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Call next in queue (AJAX)
     */
    public function callNext(Request $request)
    {
        try {
            // Get next waiting in queue (VIP first, then by ID)
            $next = \App\Models\Queue::where('status', 'waiting')
                ->orderBy('is_vip', 'desc')
                ->orderBy('id', 'asc')
                ->first();

            if (!$next) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد أحد في قائمة الانتظار'
                ]);
            }

            // Set this one as serving (allow multiple serving at same time)
            $next->update(['status' => 'serving']);

            return response()->json([
                'success' => true,
                'message' => 'الدور رقم #' . $next->queue_number . ' - ' . ($next->appointment?->customer?->name ?? 'غير محدد'),
                'data' => $next->load(['appointment.customer'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error calling next in queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Serve queue item (AJAX)
     */
    public function serveQueue($id)
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);

            // Just update current queue to serving (don't auto-complete other serving queues)
            $queue->update(['status' => 'serving']);

            return response()->json([
                'success' => true,
                'message' => 'جاري خدمة العميل'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error serving queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Complete queue item (AJAX)
     */
    public function completeQueue($id)
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);

            $queue->update([
                'status' => 'completed'
            ]);

            // Update appointment status
            if ($queue->appointment) {
                $queue->appointment->update(['status' => 'confirmed']);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنهاء الخدمة بنجاح'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error completing queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Return queue item to waiting (AJAX)
     */
    public function returnToWaiting($id)
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);

            $queue->update(['status' => 'waiting']);

            return response()->json([
                'success' => true,
                'message' => 'تم إرجاع العميل لقائمة الانتظار'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error returning to waiting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Set queue priority (AJAX)
     */
    public function setQueuePriority(Request $request, $id)
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);

            $validated = $request->validate([
                'priority' => 'required|boolean',
            ]);

            $queue->update(['is_vip' => $validated['priority']]);

            return response()->json([
                'success' => true,
                'message' => $validated['priority'] ? 'تم تعيين الأولوية' : 'تم إلغاء الأولوية'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error setting queue priority: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Get queue details (AJAX)
     */
    public function getQueue($id)
    {
        try {
            $queue = \App\Models\Queue::with(['appointment.customer', 'appointment.staff', 'appointment.service'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $queue
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على البيانات'
            ], 404);
        }
    }

    /**
     * Update queue (AJAX)
     */
    public function updateQueue(Request $request, $id)
    {
        try {
            $queue = \App\Models\Queue::with('appointment.customer')->findOrFail($id);

            // Update customer data
            if ($queue->appointment && $queue->appointment->customer) {
                $queue->appointment->customer->update([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'email' => $request->customer_email ?: null,
                ]);
            }

            // Update VIP status and notes
            $queue->update([
                'is_vip' => $request->is_vip ? true : false,
                'notes' => $request->notes ?: null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Remove from queue (AJAX)
     */
    public function removeQueue($id)
    {
        try {
            $queue = \App\Models\Queue::findOrFail($id);
            $queue->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العميل من القائمة'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error removing from queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    /**
     * Move queue items to next day (AJAX)
     */
    public function moveQueueToNextDay(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'status' => 'required|in:waiting,serving',
            ]);

            $date = $validated['date'];
            $status = $validated['status'];

            // Calculate next day
            $nextDay = \Carbon\Carbon::parse($date)->addDay();

            // Get queues to move
            $queues = \App\Models\Queue::whereDate('created_at', $date)
                ->where('status', $status)
                ->get();

            if ($queues->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد أدوار لنقلها'
                ]);
            }

            $count = $queues->count();

            // Move to next day by updating created_at and updated_at
            foreach ($queues as $queue) {
                $queue->created_at = $nextDay;
                $queue->updated_at = now();
                $queue->save();
            }

            $statusText = $status === 'waiting' ? 'المنتظرين' : 'المتخدمين';

            return response()->json([
                'success' => true,
                'message' => "تم نقل {$count} من {$statusText} إلى يوم " . $nextDay->format('Y-m-d')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error moving queue to next day: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ'
            ], 500);
        }
    }

    // ==================== SETTINGS ====================

    /**
     * Show settings page
     */
    public function settings()
    {
        $tenant = tenant();
        $settingsModel = \App\Models\Setting::where('tenant_id', $tenant->id)->first();

        $settings = $settingsModel ? $settingsModel->toArray() : [];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Save business settings
     */
    public function saveSettings(Request $request)
    {
        try {
            $tenant = tenant();

            $data = $request->validate([
                'business_name' => 'nullable|string|max:255',
                'business_name_ar' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
                'whatsapp' => 'nullable|string|max:50',
                'facebook' => 'nullable|url|max:255',
                'instagram' => 'nullable|url|max:255',
                'twitter' => 'nullable|url|max:255',
                'tiktok' => 'nullable|url|max:255',
                'snapchat' => 'nullable|string|max:100',
            ]);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoPath = $logo->store('logos/' . $tenant->id, 'public');
                $data['logo'] = $logoPath;
            }

            $settings = \App\Models\Setting::updateOrCreate(
                ['tenant_id' => $tenant->id],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => __('Settings saved successfully!'),
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving settings: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store new service
     */
    public function storeService(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'name_ar' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'duration' => 'required|integer|min:5|max:480',
                'price' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            $service = Service::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الخدمة بنجاح',
                'data' => $service
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating service: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Get service details
     */
    public function showService($id)
    {
        $service = Service::findOrFail($id);
        return response()->json(['success' => true, 'data' => $service]);
    }

    /**
     * Update service
     */
    public function updateService(Request $request, $id)
    {
        try {
            $service = Service::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'name_ar' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'duration' => 'required|integer|min:5|max:480',
                'price' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            $service->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل الخدمة بنجاح',
                'data' => $service
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating service: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Delete service
     */
    public function destroyService($id)
    {
        try {
            $service = Service::findOrFail($id);
            $service->delete();

            return response()->json(['success' => true, 'message' => 'تم حذف الخدمة بنجاح']);
        } catch (\Exception $e) {
            \Log::error('Error deleting service: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Store new time slot
     */
    public function storeTimeSlot(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_time' => 'required',
                'end_time' => 'required|after:start_time',
            ]);

            $timeSlot = TimeSlot::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الوقت بنجاح',
                'data' => $timeSlot
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating time slot: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Toggle time slot status
     */
    public function toggleTimeSlot(Request $request, $id)
    {
        try {
            $timeSlot = TimeSlot::findOrFail($id);
            $timeSlot->update(['is_active' => $request->is_active]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Delete time slot
     */
    public function destroyTimeSlot($id)
    {
        try {
            TimeSlot::findOrFail($id)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Toggle working day status
     */
    public function toggleWorkingDay(Request $request, $id)
    {
        try {
            $workingDay = WorkingDay::findOrFail($id);
            $workingDay->update(['is_active' => $request->is_active]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Toggle staff service assignment
     */
    public function toggleStaffService(Request $request)
    {
        try {
            $user = User::findOrFail($request->staff_id);

            if ($request->attach) {
                $user->services()->syncWithoutDetaching([$request->service_id]);
            } else {
                $user->services()->detach($request->service_id);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error toggling staff service: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get services for dropdown (API)
     */
    public function getServices()
    {
        $services = Service::active()->get();
        return response()->json(['success' => true, 'data' => $services]);
    }

    /**
     * Get time slots for dropdown (API)
     */
    public function getTimeSlots()
    {
        $timeSlots = TimeSlot::active()->orderBy('start_time')->get();
        return response()->json(['success' => true, 'data' => $timeSlots]);
    }

    /**
     * Get available time slots for a specific date and staff (API)
     */
    public function getAvailableTimeSlots(Request $request)
    {
        try {
            $date = $request->input('date');
            $staffId = $request->input('staff_id');
            $excludeAppointmentId = $request->input('exclude_appointment_id'); // للتعديل: نستثني الموعد الحالي

            Log::info('getAvailableTimeSlots called', [
                'date' => $date,
                'staff_id' => $staffId,
                'exclude_id' => $excludeAppointmentId
            ]);

            // Check if date is in the past
            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Cannot book appointments for past dates',
                    'reason' => 'past_date'
                ]);
            }

            // Get all active time slots
            $allTimeSlots = TimeSlot::active()->orderBy('start_time')->get();
            Log::info('Active time slots count: ' . $allTimeSlots->count());

            // Get the day of week (0 = Sunday, 1 = Monday, etc.)
            $dayOfWeek = date('w', strtotime($date));
            Log::info('Day of week: ' . $dayOfWeek);

            // Check if staff works on this day
            $staffSchedule = StaffSchedule::where('user_id', $staffId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();

            Log::info('Staff schedule found: ' . ($staffSchedule ? 'Yes' : 'No'), [
                'staff_id' => $staffId,
                'day_of_week' => $dayOfWeek,
                'schedule' => $staffSchedule ? [
                    'from_time' => $staffSchedule->start_time,
                    'to_time' => $staffSchedule->end_time
                ] : null
            ]);

            // If staff doesn't work on this day, return empty array
            if (!$staffSchedule) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Staff is not available on this day',
                    'reason' => 'staff_not_working'
                ]);
            }

            // Get booked time slots for this date and staff
            $bookedSlots = Appointment::where('date', $date)
                ->where('staff_id', $staffId)
                ->whereNotIn('status', ['cancelled'])
                ->when($excludeAppointmentId, function($query) use ($excludeAppointmentId) {
                    return $query->where('id', '!=', $excludeAppointmentId);
                })
                ->pluck('time_slot')
                ->toArray();

            // Filter out booked slots and slots outside staff working hours
            $availableSlots = $allTimeSlots->filter(function($slot) use ($bookedSlots, $staffSchedule) {
                // Check if slot is not booked
                if (in_array($slot->start_time, $bookedSlots)) {
                    return false;
                }

                // Check if slot is within staff working hours
                $slotTime = $slot->start_time;
                $staffFromTime = $staffSchedule->start_time;
                $staffToTime = $staffSchedule->end_time;

                return $slotTime >= $staffFromTime && $slotTime < $staffToTime;
            })->values();

            Log::info('Available slots count: ' . $availableSlots->count());

            return response()->json(['success' => true, 'data' => $availableSlots]);

        } catch (\Exception $e) {
            Log::error('Error in getAvailableTimeSlots: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error loading time slots: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get working days (API)
     */
    public function getWorkingDays()
    {
        $workingDays = WorkingDay::active()->orderBy('day_of_week')->get();
        return response()->json(['success' => true, 'data' => $workingDays]);
    }

    /**
     * Get staff services (API)
     */
    public function getStaffServices($staffId)
    {
        $user = User::with('services')->findOrFail($staffId);
        return response()->json(['success' => true, 'data' => $user->services]);
    }

    // ==================== STAFF MANAGEMENT ====================

    /**
     * Show staff management page
     */
    public function staff()
    {
        $staffRole = Role::where('name', 'Staff')->first();
        $staffMembers = User::where('role_id', $staffRole?->id)
            ->with(['services', 'activeSchedules'])
            ->get();

        $services = Service::orderBy('name')->get();

        return view('admin.staff.index', compact('staffMembers', 'services'));
    }

    /**
     * Get staff member details (API)
     */
    public function showStaff($id)
    {
        try {
            $staff = User::with(['services', 'schedules'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $staff]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Staff not found'], 404);
        }
    }

    /**
     * Store new staff member (API)
     */
    public function storeStaff(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'specialization' => 'required|string|max:255',
                'specialization_ar' => 'nullable|string|max:255',
                'services' => 'array',
                'schedule' => 'array',
            ]);

            $staffRole = Role::where('name', 'Staff')->first();
            if (!$staffRole) {
                return response()->json(['success' => false, 'message' => 'Staff role not found'], 500);
            }

            DB::beginTransaction();

            // Generate default password (using email prefix or default)
            $defaultPassword = explode('@', $validated['email'])[0] . '123';

            // Create user
            $user = User::create([
                'role_id' => $staffRole->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($defaultPassword),
                'specialization' => $validated['specialization'],
                'specialization_ar' => $validated['specialization_ar'] ?? null,
            ]);

            // Attach services
            if (!empty($request->services)) {
                $user->services()->sync($request->services);
            }

            // Create schedule
            if (!empty($request->schedule)) {
                foreach ($request->schedule as $schedule) {
                    StaffSchedule::create([
                        'user_id' => $user->id,
                        'day_of_week' => $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'is_active' => $schedule['is_active'] ?? true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الموظف بنجاح. الباسورد الافتراضي: ' . $defaultPassword,
                'data' => $user->load(['services', 'schedules']),
                'default_password' => $defaultPassword
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating staff: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Update staff member (API)
     */
    public function updateStaff(Request $request, $id)
    {
        try {
            $staff = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'specialization' => 'nullable|string|max:255',
                'specialization_ar' => 'nullable|string|max:255',
                'services' => 'array',
                'schedule' => 'array',
            ]);

            DB::beginTransaction();

            // Update user
            $staff->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'specialization' => $validated['specialization'] ?? $staff->specialization,
                'specialization_ar' => $validated['specialization_ar'] ?? $staff->specialization_ar,
            ]);

            // Sync services
            $staff->services()->sync($request->services ?? []);

            // Update schedule - delete old and create new
            StaffSchedule::where('user_id', $staff->id)->delete();

            if (!empty($request->schedule)) {
                foreach ($request->schedule as $schedule) {
                    StaffSchedule::create([
                        'user_id' => $staff->id,
                        'day_of_week' => $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'is_active' => $schedule['is_active'] ?? true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل الموظف بنجاح',
                'data' => $staff->load(['services', 'schedules'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating staff: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Delete staff member (API)
     */
    public function destroyStaff($id)
    {
        try {
            $staff = User::findOrFail($id);

            // Delete schedules
            StaffSchedule::where('user_id', $staff->id)->delete();

            // Detach services
            $staff->services()->detach();

            // Delete user
            $staff->delete();

            return response()->json(['success' => true, 'message' => 'تم حذف الموظف بنجاح']);
        } catch (\Exception $e) {
            \Log::error('Error deleting staff: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Get staff by specialization (API for queue form)
     */
    public function getStaffBySpecialization($specialization)
    {
        try {
            $staff = User::where('specialization', $specialization)
                ->whereHas('role', function($q) {
                    $q->where('name', 'Staff');
                })
                ->get(['id', 'name', 'specialization', 'specialization_ar']);

            return response()->json(['success' => true, 'data' => $staff]);
        } catch (\Exception $e) {
            \Log::error('Error getting staff by specialization: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }

    /**
     * Get staff services as JSON (API for queue form)
     */
    public function getStaffServicesJson($id)
    {
        try {
            $staff = User::findOrFail($id);
            $services = $staff->services()->get(['services.id', 'name', 'name_ar', 'duration', 'price']);

            return response()->json(['success' => true, 'data' => $services]);
        } catch (\Exception $e) {
            \Log::error('Error getting staff services: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }

    /**
     * Get staff by service (API for booking)
     */
    public function getStaffByService($serviceId)
    {
        try {
            $staff = User::whereHas('services', function($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            })
            ->whereHas('role', function($q) {
                $q->where('name', 'Staff');
            })
            ->with(['activeSchedules'])
            ->get(['id', 'name']);

            return response()->json(['success' => true, 'data' => $staff]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }

    /**
     * Get staff schedule (API for booking)
     */
    public function getStaffSchedule($staffId)
    {
        try {
            $schedules = StaffSchedule::where('user_id', $staffId)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->get();

            return response()->json(['success' => true, 'data' => $schedules]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error'], 500);
        }
    }
}
