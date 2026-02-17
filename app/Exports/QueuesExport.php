<?php

namespace App\Exports;

use App\Models\Queue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QueuesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Queue::with(['appointment.customer', 'appointment.staff', 'appointment.service'])
            ->whereIn('status', ['waiting', 'serving'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'رقم الدور',
            'اسم العميل',
            'رقم الهاتف',
            'البريد الإلكتروني',
            'الموظف',
            'الخدمة',
            'الأولوية',
            'ملاحظات',
            'الحالة',
        ];
    }

    /**
     * @param Queue $queue
     * @return array
     */
    public function map($queue): array
    {
        $statusMap = [
            'waiting' => 'في الانتظار',
            'serving' => 'يتم الخدمة',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
        ];

        return [
            $queue->queue_number,
            $queue->appointment->customer->name ?? '-',
            $queue->appointment->customer->phone ?? '-',
            $queue->appointment->customer->email ?? '-',
            $queue->appointment->staff->name ?? '-',
            $queue->appointment->service->name ?? '-',
            $queue->is_vip ? 'VIP' : 'عادي',
            $queue->notes ?? '-',
            $statusMap[$queue->status] ?? $queue->status,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // رقم الدور
            'B' => 20,  // اسم العميل
            'C' => 15,  // رقم الهاتف
            'D' => 25,  // البريد الإلكتروني
            'E' => 20,  // الموظف
            'F' => 20,  // الخدمة
            'G' => 12,  // الأولوية
            'H' => 30,  // ملاحظات
            'I' => 15,  // الحالة
        ];
    }
}
