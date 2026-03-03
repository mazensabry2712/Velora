<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_appointments'     => Appointment::count(),
            'confirmed_appointments' => Appointment::where('status', 'confirmed')->count(),
            'pending_appointments'   => Appointment::where('status', 'pending')->count(),
            'total_customers'        => User::whereHas('role', fn ($q) => $q->where('name', 'Customer'))->count(),
        ];

        $appointmentsByStatus = Appointment::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $queueStats = [
            'waiting'  => Queue::where('status', 'waiting')->count(),
            'serving'  => Queue::where('status', 'serving')->count(),
            'completed' => Queue::where('status', 'completed')->count(),
            'priority' => Queue::where('is_vip', true)->whereIn('status', ['waiting', 'serving'])->count(),
        ];

        $staffPerformance = User::whereHas('role', fn ($q) => $q->whereIn('name', ['Admin Tenant', 'Staff']))
            ->withCount(['staffAppointments' => fn ($q) => $q->where('status', 'confirmed')])
            ->having('staff_appointments_count', '>', 0)
            ->orderByDesc('staff_appointments_count')
            ->get();

        $serviceTypes = Appointment::whereNotNull('service_type')
            ->select('service_type', DB::raw('count(*) as count'))
            ->groupBy('service_type')
            ->orderByDesc('count')
            ->get();

        return view('admin.reports.index', compact(
            'stats', 'appointmentsByStatus', 'queueStats', 'staffPerformance', 'serviceTypes'
        ));
    }

    public function exportAppointments(\Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
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
}
