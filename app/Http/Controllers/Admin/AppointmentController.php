<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Booking\Actions\AddAppointmentToQueue;
use App\Application\Booking\Actions\ChangeAppointmentStatus;
use App\Application\Booking\Actions\CompleteBulkAppointmentStatusUpdate;
use App\Application\Booking\Actions\CreateAdminAppointment;
use App\Application\Booking\Actions\DeleteAppointment;
use App\Application\Booking\Actions\RemoveAppointmentFromQueue;
use App\Application\Booking\Actions\UpdateAdminAppointment;
use App\Application\Booking\DTOs\CreateAdminAppointmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppointmentRequest;
use App\Http\Requests\Admin\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Image\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly CreateAdminAppointment $createAppointment,
        private readonly UpdateAdminAppointment $updateAppointment,
        private readonly ChangeAppointmentStatus $changeStatus,
        private readonly DeleteAppointment $deleteAppointment,
        private readonly AddAppointmentToQueue $addToQueue,
        private readonly RemoveAppointmentFromQueue $removeFromQueue,
        private readonly CompleteBulkAppointmentStatusUpdate $bulkStatusUpdate,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'date_filter', 'date_from', 'date_to', 'status',
            'staff_id', 'search', 'sort', 'dir',
        ]);

        $perPage = in_array($request->get('per_page'), [5, 10, 15, 50, 75, 100], true)
            ? (int) $request->get('per_page')
            : 15;

        $paginatedData = $this->appointments->paginate($filters, $perPage);

        $all = Appointment::with(['customer', 'staff', 'service', 'queue'])
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['staff_id']), fn ($q) => $q->where('staff_id', $filters['staff_id']))
            ->when(! empty($filters['date_filter']), fn ($q) => match ($filters['date_filter']) {
                'today' => $q->whereDate('date', today()),
                'week' => $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $q->whereMonth('date', now()->month)->whereYear('date', now()->year),
                default => $q,
            })
            ->orderByDesc('date')->orderBy('time_slot')->get();

        $appointmentsByDate = $all
            ->groupBy(fn ($a) => $a->date->format('Y-m-d'))
            ->map(function ($day, $date) {
                $total = $day->count();
                $dateCarbon = \Carbon\Carbon::parse($date);

                return [
                    'date' => $date,
                    'date_formatted' => $dateCarbon->translatedFormat('l, F j, Y'),
                    'is_today' => $dateCarbon->isToday(),
                    'is_past' => $dateCarbon->isPast() && ! $dateCarbon->isToday(),
                    'diff_humans' => $dateCarbon->diffForHumans(),
                    'appointments' => $day->sortBy('time_slot')->values(),
                    'appointment_ids' => $day->pluck('id')->toArray(),
                    'total' => $total,
                    'confirmed' => $day->where('status', 'confirmed')->count(),
                    'pending' => $day->where('status', 'pending')->count(),
                    'completed' => $day->where('status', 'completed')->count(),
                    'cancelled' => $day->where('status', 'cancelled')->count(),
                    'confirmed_percent' => $total > 0 ? round(($day->where('status', 'confirmed')->count() / $total) * 100) : 0,
                    'pending_percent' => $total > 0 ? round(($day->where('status', 'pending')->count() / $total) * 100) : 0,
                    'completed_percent' => $total > 0 ? round(($day->where('status', 'completed')->count() / $total) * 100) : 0,
                    'cancelled_percent' => $total > 0 ? round(($day->where('status', 'cancelled')->count() / $total) * 100) : 0,
                    'progress_percent' => $total > 0 ? round(($day->whereIn('status', ['completed', 'cancelled'])->count() / $total) * 100) : 0,
                    'is_tomorrow' => $dateCarbon->isTomorrow(),
                    'revenue' => $day->sum(fn ($a) => $a->service?->price ?? 0),
                ];
            })
            ->sortKeysDesc()->values();

        $stats = $this->appointments->getTodayStats();
        $stats['no_show_rate'] = $this->noShowRate();
        $stats['this_week'] = $this->appointments->getWeeklyStats()['this_week'];
        $stats['cancelled_month'] = Appointment::where('status', 'cancelled')->whereMonth('date', now()->month)->count();
        $stats['avg_daily'] = $this->avgDaily();

        $staffMembers = User::role(['Staff', 'Admin Tenant'])->get();
        $services = Service::where('is_active', true)->get();

        return view('admin.appointments.index', compact(
            'paginatedData', 'appointmentsByDate', 'stats', 'services', 'staffMembers'
        ));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->createAppointment->execute(new CreateAdminAppointmentData(
                customerName: $data['customer_name'],
                customerPhone: $data['customer_phone'],
                customerEmail: $data['customer_email'] ?? null,
                staffId: $data['staff_id'] ?? auth()->id(),
                serviceId: $data['service_id'] ?? null,
                appointmentDate: $data['appointment_date'],
                appointmentTime: $data['appointment_time'],
                serviceType: $data['service_type'] ?? null,
                notes: $data['notes'] ?? null,
                addToQueue: $request->boolean('add_to_queue'),
                queueDate: $data['queue_date'] ?? null,
            ));

            return response()->json([
                'success' => true,
                'message' => __('Appointment saved successfully.'),
                'data' => $result['appointment'],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('storeAppointment: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $a = $this->appointments->findWithRelations($id, ['customer', 'staff']);

            return response()->json(['success' => true, 'data' => [
                'id' => $a->id,
                'customer_name' => $a->customer?->name ?? '-',
                'customer_phone' => $a->customer?->phone ?? '-',
                'customer_email' => $a->customer?->email ?? '-',
                'date' => $a->date->format('Y-m-d'),
                'time_slot' => $a->time_slot,
                'service_type' => $a->service_type,
                'notes' => $a->notes,
                'status' => $a->status,
                'staff_name' => $a->staff?->name ?? '-',
                'staff_id' => $a->staff_id,
            ]]);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => __('Not found')], 404);
        }
    }

    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        try {
            $appointment = $this->updateAppointment->execute($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('Appointment updated.'),
                'data' => $appointment,
            ]);
        } catch (\Throwable $e) {
            Log::error('updateAppointment: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function quickStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled,completed']);

        try {
            $appointment = $this->changeStatus->execute($id, (string) $request->string('status'));

            return response()->json([
                'success' => true,
                'message' => __('Status updated.'),
                'data' => $appointment,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteAppointment->execute($id);

            return response()->json(['success' => true, 'message' => __('Appointment deleted.')]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addToQueue(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'queue_date' => 'nullable|date|after_or_equal:today',
            ]);

            $queue = $this->addToQueue->execute($id, $validated['queue_date'] ?? null);

            return response()->json([
                'success' => true,
                'message' => __('Added to queue.'),
                'data' => [
                    'queue' => $queue,
                    'appointment' => $this->appointments->findWithRelations($id, ['queue']),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function removeFromQueue(int $id): JsonResponse
    {
        try {
            $appointment = $this->removeFromQueue->execute($id);

            return response()->json([
                'success' => true,
                'message' => __('Removed from queue.'),
                'data' => $appointment,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sendReminder(int $id): JsonResponse
    {
        try {
            $a = $this->appointments->findWithRelations($id, ['customer', 'service', 'staff']);

            if (in_array($a->status, ['cancelled', 'completed'], true)) {
                return response()->json(['success' => false, 'message' => __('Cannot send reminder for this status.')], 400);
            }

            if ($a->date->lt(now())) {
                return response()->json(['success' => false, 'message' => __('Past appointment.')], 400);
            }

            Mail::to($a->customer->email)->send(
                new \App\Mail\AppointmentReminderMail($a, $a->customer, app()->getLocale())
            );

            return response()->json(['success' => true, 'message' => __('Reminder sent.')]);
        } catch (\Throwable $e) {
            Log::error('sendReminder: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkDayAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:confirm_all,complete_all',
            'appointment_ids' => 'required|array',
            'appointment_ids.*' => 'exists:appointments,id',
        ]);

        $updated = $this->bulkStatusUpdate->execute(
            array_map('intval', $data['appointment_ids']),
            $data['action'],
        );

        return response()->json([
            'success' => true,
            'message' => "{$updated} appointments updated.",
            'updated_count' => $updated,
        ]);
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
                'Phone: ' . ($a->customer?->phone ?? 'N/A'),
                'Service: ' . ($a->service?->name ?? $a->service_type ?? 'N/A'),
                'Staff: ' . ($a->staff?->name ?? 'N/A'),
                'Date: ' . $a->date->format('Y-m-d'),
                'Time: ' . $a->time_slot,
                'Status: ' . ucfirst($a->status),
                '==================',
                'Tenant: ' . tenant()->name,
            ]);

            $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
            $svg = (new Writer($renderer))->writeString($qrData);

            return response($svg)->header('Content-Type', 'image/svg+xml');
        } catch (\Throwable $e) {
            Log::error('generateQRCode: ' . $e->getMessage());
            return response('QR Code generation failed', 500);
        }
    }

    public function rate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'rating_comment' => 'nullable|string|max:1000',
        ]);

        try {
            $a = $this->appointments->findById($id);

            if ($a->status !== 'completed') {
                return response()->json(['success' => false, 'message' => __('Only completed appointments can be rated.')], 422);
            }

            $this->appointments->update($a, [
                'rating' => $request->integer('rating'),
                'rating_comment' => $request->input('rating_comment'),
                'rated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Rating saved.'),
                'data' => $a->only(['rating', 'rating_comment', 'rated_at']),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $period = $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $fileName = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new \App\Exports\AppointmentsExport(tenant(), $period, $startDate, $endDate),
            $fileName,
        );
    }

    private function noShowRate(): float
    {
        $past = Appointment::where('date', '<', today()->format('Y-m-d'))->count();
        $completed = Appointment::where('date', '<', today()->format('Y-m-d'))
            ->where('status', 'completed')->count();

        return $past > 0 ? round((($past - $completed) / $past) * 100, 1) : 0;
    }

    private function avgDaily(): float
    {
        $count = Appointment::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)->count();

        return now()->day > 0 ? round($count / now()->day, 1) : 0;
    }
}
