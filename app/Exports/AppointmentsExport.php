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
     * Query for appointments
     */
    public function query()
    {
        $query = Appointment::query()
            ->with(['customer', 'staff']);

        // Apply date filters
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        } else {
            switch ($this->period) {
                case 'today':
                    $query->whereDate('date', now());
                    break;
                case 'week':
                    $query->whereBetween('date', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('date', now()->month)
                        ->whereYear('date', now()->year);
                    break;
            }
        }

        return $query->orderBy('date');
    }

    /**
     * Define column headings
     */
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

    /**
     * Map data for each row
     */
    public function map($appointment): array
    {
        return [
            $appointment->id,
            $appointment->customer->name ?? 'N/A',
            $appointment->customer->email ?? 'N/A',
            $appointment->customer->phone ?? 'N/A',
            $appointment->staff->name ?? 'N/A',
            $appointment->date ? $appointment->date->format('Y-m-d') : 'N/A',
            $appointment->time_slot ?? 'N/A',
            $appointment->service_type ?? 'N/A',
            $appointment->status ?? 'N/A',
            $appointment->notes ?? '',
            $appointment->created_at ? $appointment->created_at->format('Y-m-d H:i') : 'N/A',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
