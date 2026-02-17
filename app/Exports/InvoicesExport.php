<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $tenant;
    protected $period;

    public function __construct($tenant, $period = 'month')
    {
        $this->tenant = $tenant;
        $this->period = $period;
    }

    /**
     * Query for invoices
     */
    public function query()
    {
        $query = Invoice::query()
            ->where('tenant_id', $this->tenant->id)
            ->with(['customer', 'appointment']);

        // Apply date filters
        switch ($this->period) {
            case 'today':
                $query->whereDate('created_at', now());
                break;
            case 'week':
                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Invoice Number',
            'Customer Name',
            'Customer Email',
            'Appointment Date',
            'Total Amount',
            'Tax Amount',
            'Discount',
            'Status',
            'Payment Method',
            'Issued Date',
            'Due Date',
            'Paid At',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->customer->name ?? 'N/A',
            $invoice->customer->email ?? 'N/A',
            $invoice->appointment ? $invoice->appointment->appointment_date->format('Y-m-d H:i') : 'N/A',
            number_format($invoice->total_amount, 2),
            number_format($invoice->tax_amount ?? 0, 2),
            number_format($invoice->discount ?? 0, 2),
            $invoice->status,
            $invoice->payment_method ?? 'N/A',
            $invoice->created_at->format('Y-m-d'),
            $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A',
            $invoice->paid_at ? $invoice->paid_at->format('Y-m-d H:i') : 'N/A',
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
