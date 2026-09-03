<?php

namespace App\Exports;

use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AppointmentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $tenant;
    protected $period;
    protected $startDate;
    protected $endDate;

    public function __construct($tenant, $period = 'month', $startDate = null, $endDate = null)
    {
        $this->tenant = $tenant;
        $this->period = $period;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Query for appointments.
     */
    public function query()
    {
        $query = Appointment::query()
            ->with(['customer', 'staff']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('starts_at', [$this->startDate, $this->endDate]);
        } else {
            switch ($this->period) {
                case 'today':
                    $query->whereDate('starts_at', now());
                    break;
                case 'week':
                    $query->whereBetween('starts_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('starts_at', now()->month)
                        ->whereYear('starts_at', now()->year);
                    break;
            }
        }

        return $query->orderBy('starts_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'اسم العميل / Customer Name',
            'البريد الإلكتروني / Email',
            'الهاتف / Phone',
            'الموظف / Staff',
            'التاريخ / Date',
            'الوقت / Time',
            'نوع الخدمة / Service',
            'الحالة / Status',
            'ملاحظات / Notes',
            'تاريخ الإنشاء / Created At',
        ];
    }

    public function map($appointment): array
    {
        $startsAt = $appointment->starts_at;

        return [
            $appointment->id,
            $appointment->customer->name ?? 'N/A',
            $appointment->customer->email ?? 'N/A',
            $appointment->customer->phone ?? 'N/A',
            $appointment->staff->name ?? 'N/A',
            $startsAt?->format('Y-m-d') ?? 'N/A',
            $startsAt?->format('H:i') ?? 'N/A',
            $appointment->service_type ?? 'N/A',
            $appointment->status ?? 'N/A',
            $appointment->notes ?? '',
            $appointment->created_at ? $appointment->created_at->format('Y-m-d H:i') : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
