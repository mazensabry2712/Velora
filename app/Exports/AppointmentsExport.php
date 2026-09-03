<?php

namespace App\Exports;

use App\Models\Appointment;
use App\Services\ReportService;
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

    public function query()
    {
        $query = Appointment::query()
            ->with(['customer', 'staff']);

        [$start, $end] = app(ReportService::class)->resolveRange(
            (string) $this->period,
            $this->startDate,
            $this->endDate,
        );

        if ($start && $end) {
            $query->whereBetween('starts_at', [$start, $end]);
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
