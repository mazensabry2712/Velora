<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppointmentRequest;
use App\Http\Requests\Admin\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Models\UsageLog;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
    ) {}

    // ── Page views ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $filters = $request->only([
            'date_filter',
            'date_from',
            'date_to',
            'status',
            'staff_id',
            'search',
            'sort',
            'dir',
        ]);

        $perPage = in_array($request->get('per_page'), [5, 10, 15, 50, 75, 100])
            ? (int) $request->get('per_page')
            : 15;

        $paginatedData = $this->appointments->paginate($filters, $perPage);

        // Build grouped-by-date view from all filtered appointments
        $all = Appointment::with(['customer', 'staff', 'service', 'queue'])
            ->when(!empty($filters['status']) && $filters['status'] !== 'all', fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['staff_id']), fn($q) => $q->where('staff_id', $filters['staff_id']))
            ->when(!empty($filters['date_filter']), fn($q) => match ($filters['date_filter']) {
                'today'  => $q->whereDate('date', today()),
                'week'   => $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
                'month'  => $q->whereMonth('date', now()->month)->whereYear('date', now()->year),
                default  => $q,
            })
            ->orderByDesc('date')->orderBy('time_slot')->get();

        $appointmentsByDate = $all
            ->groupBy(fn($a) => $a->date->format('Y-m-d'))
            ->map(function ($day, $date) {
                $total     = $day->count();
                $dateCarbon = \Carbon\Carbon::parse($date);
                return [
                    'date'              => $date,
                    'date_formatted'    => $dateCarbon->translatedFormat('l, F j, Y'),
                    'is_today'          => $dateCarbon->isToday(),
                    'is_past'           => $dateCarbon->isPast() && !$dateCarbon->isToday(),
                    'diff_humans'       => $dateCarbon->diffForHumans(),
                    'appointments'      => $day->sortBy('time_slot')->values(),
                    'appointment_ids'   => $day->pluck('id')->toArray(),
                    'total'             => $total,
                    'confirmed'          => $day->where('status', 'confirmed')->count(),
                    'pending'            => $day->where('status', 'pending')->count(),
                    'completed'          => $day->where('status', 'completed')->count(),
                    'cancelled'          => $day->where('status', 'cancelled')->count(),
                    'confirmed_percent'  => $total > 0 ? round(($day->where('status', 'confirmed')->count() / $total) * 100) : 0,
                    'pending_percent'    => $total > 0 ? round(($day->where('status', 'pending')->count() / $total) * 100) : 0,
                    'completed_percent'  => $total > 0 ? round(($day->where('status', 'completed')->count() / $total) * 100) : 0,
                    'cancelled_percent'  => $total > 0 ? round(($day->where('status', 'cancelled')->count() / $total) * 100) : 0,
                    'progress_percent'   => $total > 0 ? round(($day->whereIn('status', ['completed', 'cancelled'])->count() / $total) * 100) : 0,
                    'is_tomorrow'        => $dateCarbon->isTomorrow(),
                    'revenue'            => $day->sum(fn($a) => $a->service?->price ?? 0),
                ];
            })
            ->sortKeysDesc()->values();

        $stats = $this->appointments->getTodayStats();
        $stats['no_show_rate']  = $this->noShowRate();
        $stats['this_week']     = $this->appointments->getWeeklyStats()['this_week'];
        $stats['cancelled_month'] = Appointment::where('status', 'cancelled')->whereMonth('date', now()->month)->count();
        $stats['avg_daily']     = $this->avgDaily();

        $staffMembers = User::role(['Staff', 'Admin Tenant'])->get();
        $services    = Service::where('is_active', true)->get();

        $appointments = $paginatedData;

        return view('admin.appointments.index', compact(
            'paginatedData',
            'appointments',
            'appointmentsByDate',
            'stats',
            'services',
            'staffMembers'
        ));
    }

    // ── JSON API ─────────────────────────────────────────────────────────

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $customer = $this->findOrCreateCustomer($data);

            $appointment = $this->appointments->create([
                'customer_id'  => $customer->id,
                'staff_id'     => $data['staff_id'] ?? auth()->id(),
                'service_id'   => $data['service_id'] ?? null,
                'date'         => $data['appointment_date'],
                'time_slot'    => $data['appointment_time'],
                'status'       => $request->boolean('add_to_queue') ? 'confirmed' : 'pending',
                'service_type' => $data['service_type'] ?? null,
                'notes'        => $data['notes'] ?? null,
            ]);

            try {
                UsageLog::log('appointment_created', [
                    'appointment_id' => $appointment->id,
                    'customer_id'    => $customer->id,
                    'service_id'     => $appointment->service_id,
                    'date'           => $appointment->date,
                ]);
            } catch (\Throwable) {
            }

            $queueData = null;
            if ($request->boolean('add_to_queue')) {
                $queueDate = $data['queue_date'] ?? $data['appointment_date'];
                $queueData = Queue::create([
                    'appointment_id' => $appointment->id,
                    'queue_number'   => Queue::generateQueueNumber(),
                    'queue_date'     => $queueDate,
                    'status'         => 'waiting',
                    'is_vip'         => $customer->is_vip ?? false,
                ]);
            }

            $appointment->load(['customer', 'staff', 'service', 'queue']);

            return response()->json([
                'success' => true,
                'message' => __('Appointment saved successfully.'),
                'data'    => $appointment,
            ], 201);
        } catch (\Exception $e) {
            Log::error('storeAppointment: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $a = $this->appointments->findWithRelations($id, ['customer', 'staff']);

            return response()->json(['success' => true, 'data' => [
                'id'           => $a->id,
                'customer_name'  => $a->customer?->name ?? '-',
                'customer_phone' => $a->customer?->phone ?? '-',
                'customer_email' => $a->customer?->email ?? '-',
                'date'         => $a->date->format('Y-m-d'),
                'time_slot'    => $a->time_slot,
                'service_type' => $a->service_type,
                'notes'        => $a->notes,
                'status'       => $a->status,
                'staff_name'   => $a->staff?->name ?? '-',
                'staff_id'     => $a->staff_id,
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Not found')], 404);
        }
    }

    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        try {
            $a    = $this->appointments->findWithRelations($id, ['customer', 'queue']);
            $data = $request->validated();

            $dateChanged = $a->date->format('Y-m-d') !== $data['appointment_date'];
            $timeChanged = $a->time_slot !== $data['appointment_time'];

            if (($dateChanged || $timeChanged) && $a->queue && \Carbon\Carbon::parse($data['appointment_date'])->lt(today())) {
                $a->queue->delete();
            }

            if ($a->customer) {
                $a->customer->update([
                    'name'  => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'email' => $data['customer_email'] ?? $a->customer->email,
                ]);
            }

            $this->appointments->update($a, [
                'staff_id'     => $data['staff_id'] ?? $a->staff_id,
                'service_id'   => $data['service_id'] ?? $a->service_id,
                'date'         => $data['appointment_date'],
                'time_slot'    => $data['appointment_time'],
                'service_type' => $data['service_type'] ?? null,
                'status'       => $data['status'],
                'notes'        => $data['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Appointment updated.'),
                'data'    => $a->fresh(['customer', 'staff', 'queue']),
            ]);
        } catch (\Exception $e) {
            Log::error('updateAppointment: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function quickStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled,completed']);

        try {
            $a = $this->appointments->findById($id);
            $this->appointments->update($a, ['status' => $request->status]);

            if ($a->queue) {
                $queueStatus = match ($request->status) {
                    'cancelled' => 'skipped',
                    'completed' => 'completed',
                    'confirmed' => $a->queue->status !== 'serving' ? 'waiting' : $a->queue->status,
                    default     => $a->queue->status,
                };
                $a->queue->update(['status' => $queueStatus]);
            }

            return response()->json([
                'success' => true,
                'message' => __('Status updated.'),
                'data'    => $a->load('queue'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $a = $this->appointments->findById($id);
            $info = ['appointment_id' => $a->id, 'customer_id' => $a->customer_id, 'date' => $a->date];
            $this->appointments->delete($a);
            try {
                UsageLog::log('appointment_cancelled', $info);
            } catch (\Throwable) {
            }

            return response()->json(['success' => true, 'message' => __('Appointment deleted.')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addToQueue(Request $request, int $id): JsonResponse
    {
        try {
            $a = $this->appointments->findWithRelations($id, ['customer', 'queue']);

            if ($a->queue) {
                return response()->json(['success' => false, 'message' => __('Already in queue.')], 400);
            }

            if (!$a->canBeAddedToQueue()) {
                return response()->json(['success' => false, 'message' => __('Cannot add to queue.')], 400);
            }

            if ($a->date->lt(today())) {
                return response()->json(['success' => false, 'message' => __('Cannot queue past appointment.')], 400);
            }

            $queueDate = $request->validate(['queue_date' => 'nullable|date|after_or_equal:today'])['queue_date']
                ?? $a->date->format('Y-m-d');

            $queue = Queue::create([
                'appointment_id' => $a->id,
                'queue_number'   => Queue::generateQueueNumber(),
                'queue_date'     => $queueDate,
                'status'         => 'waiting',
                'is_vip'         => $a->customer->is_vip ?? false,
            ]);

            if ($a->status === 'pending') {
                $this->appointments->update($a, ['status' => 'confirmed']);
            }

            return response()->json([
                'success' => true,
                'message' => __('Added to queue.'),
                'data'    => ['queue' => $queue, 'appointment' => $a->fresh('queue')],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function removeFromQueue(int $id): JsonResponse
    {
        try {
            $a = $this->appointments->findWithRelations($id, ['queue']);

            if (!$a->queue) {
                return response()->json(['success' => false, 'message' => __('Not in queue.')], 400);
            }

            $queueStatus = $a->queue->status;
            $a->queue->delete();

            if ($queueStatus === 'completed') {
                $this->appointments->update($a, ['status' => 'completed']);
            } elseif (in_array($queueStatus, ['cancelled', 'skipped'])) {
                $this->appointments->update($a, ['status' => 'cancelled']);
            }

            return response()->json(['success' => true, 'message' => __('Removed from queue.'), 'data' => $a->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sendReminder(int $id): JsonResponse
    {
        try {
            $a = $this->appointments->findWithRelations($id, ['customer', 'service', 'staff']);

            if (in_array($a->status, ['cancelled', 'completed'])) {
                return response()->json(['success' => false, 'message' => __('Cannot send reminder for this status.')], 400);
            }

            if ($a->date->lt(now())) {
                return response()->json(['success' => false, 'message' => __('Past appointment.')], 400);
            }

            Mail::to($a->customer->email)->send(
                new \App\Mail\AppointmentReminderMail($a, $a->customer, app()->getLocale())
            );

            return response()->json(['success' => true, 'message' => __('Reminder sent.')]);
        } catch (\Exception $e) {
            Log::error('sendReminder: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkDayAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action'           => 'required|in:confirm_all,complete_all',
            'appointment_ids'  => 'required|array',
            'appointment_ids.*' => 'exists:appointments,id',
        ]);

        $updated = match ($data['action']) {
            'confirm_all'  => Appointment::whereIn('id', $data['appointment_ids'])->where('status', 'pending')->update(['status' => 'confirmed']),
            'complete_all' => Appointment::whereIn('id', $data['appointment_ids'])->whereIn('status', ['confirmed', 'pending'])->update(['status' => 'completed']),
        };

        return response()->json(['success' => true, 'message' => "{$updated} appointments updated.", 'updated_count' => $updated]);
    }

    public function generateQRCode(int $id): \Illuminate\Http\Response
    {
        try {
            $a = $this->appointments->findWithRelations($id, ['customer', 'staff', 'service']);

            $qrData = implode("\n", [
                'APPOINTMENT DETAILS',
                '==================',
                'ID: #' . $a->id,
                'Customer: ' . ($a->customer?->name ?? 'N/A'),
                'Phone: '    . ($a->customer?->phone ?? 'N/A'),
                'Service: '  . ($a->service?->name ?? $a->service_type ?? 'N/A'),
                'Staff: '    . ($a->staff?->name ?? 'N/A'),
                'Date: '     . $a->date->format('Y-m-d'),
                'Time: '     . $a->time_slot,
                'Status: '   . ucfirst($a->status),
                '==================',
                'Tenant: '   . tenant()->name,
            ]);

            $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
            $svg      = (new Writer($renderer))->writeString($qrData);

            return response($svg)->header('Content-Type', 'image/svg+xml');
        } catch (\Exception $e) {
            Log::error('generateQRCode: ' . $e->getMessage());
            return response('QR Code generation failed', 500);
        }
    }

    public function rate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rating'         => 'required|integer|min:1|max:5',
            'rating_comment' => 'nullable|string|max:1000',
        ]);

        try {
            $a = $this->appointments->findById($id);

            if ($a->status !== 'completed') {
                return response()->json(['success' => false, 'message' => __('Only completed appointments can be rated.')], 422);
            }

            $this->appointments->update($a, [
                'rating'         => $request->rating,
                'rating_comment' => $request->rating_comment,
                'rated_at'       => now(),
            ]);

            return response()->json(['success' => true, 'message' => __('Rating saved.'), 'data' => $a->only(['rating', 'rating_comment', 'rated_at'])]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $period    = $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');
        $fileName  = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AppointmentsExport(tenant(), $period, $startDate, $endDate),
            $fileName
        );
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function findOrCreateCustomer(array $data): User
    {
        $email = $data['customer_email'] ?? $data['customer_phone'] . '@temp.local';

        $customer = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $data['customer_name'],
                'phone'    => $data['customer_phone'],
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
            ]
        );

        if (!$customer->hasRole('Customer')) {
            $customer->assignRole('Customer');
        }

        $customer->update(['name' => $data['customer_name'], 'phone' => $data['customer_phone']]);

        return $customer;
    }

    private function noShowRate(): float
    {
        $past      = Appointment::where('date', '<', today()->format('Y-m-d'))->count();
        $completed = Appointment::where('date', '<', today()->format('Y-m-d'))->where('status', 'completed')->count();

        return $past > 0 ? round((($past - $completed) / $past) * 100, 1) : 0;
    }

    private function avgDaily(): float
    {
        $count = Appointment::whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
        return now()->day > 0 ? round($count / now()->day, 1) : 0;
    }
}
