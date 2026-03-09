<?php

namespace App\Exports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TenantsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function collection()
    {
        return Tenant::with(['domains', 'currentSubscription.plan'])->latest()->get();
    }

    public function headings(): array
    {
        return ['#', 'Company Name', 'Domain', 'Email', 'Country', 'Specialty', 'Plan', 'Amount Paid', 'Currency', 'Sub Ends At', 'Status', 'Created At'];
    }

    public function map($tenant): array
    {
        $sub = $tenant->currentSubscription;

        return [
            $tenant->getTenantKey(),
            $tenant->name ?? '—',
            $tenant->domain,
            $tenant->email ?? '—',
            $tenant->country ?? '—',
            $tenant->specialty ?? '—',
            $sub?->plan?->name ?? '—',
            $sub?->amount_paid ? number_format((float) $sub->amount_paid, 2) : '—',
            $sub ? self::currencyForCountry($tenant->country) : '—',
            $sub?->ends_at?->format('Y-m-d') ?? '—',
            $tenant->active ? 'Active' : 'Inactive',
            $tenant->created_at?->format('Y-m-d H:i') ?? '—',
        ];
    }

    /**
     * Map a country name (Arabic or English) or ISO-2 code to its ISO-4217 currency code.
     */
    private static function currencyForCountry(?string $country): string
    {
        if (! $country) {
            return 'USD';
        }

        return match (mb_strtolower(trim($country))) {
            'egypt', 'مصر', 'eg'                                                             => 'EGP',
            'saudi arabia', 'السعودية', 'المملكة العربية السعودية', 'sa', 'ksa'             => 'SAR',
            'uae', 'united arab emirates', 'الإمارات', 'الامارات', 'ae'                     => 'AED',
            'kuwait', 'الكويت', 'kw'                                                         => 'KWD',
            'qatar', 'قطر', 'qa'                                                             => 'QAR',
            'bahrain', 'البحرين', 'bh'                                                       => 'BHD',
            'jordan', 'الأردن', 'الاردن', 'jo'                                              => 'JOD',
            'oman', 'عمان', 'عُمان', 'om'                                                   => 'OMR',
            'morocco', 'المغرب', 'ma'                                                        => 'MAD',
            'tunisia', 'تونس', 'tn'                                                          => 'TND',
            'libya', 'ليبيا', 'ly'                                                           => 'LYD',
            'iraq', 'العراق', 'iq'                                                           => 'IQD',
            'lebanon', 'لبنان', 'lb'                                                         => 'LBP',
            'syria', 'سوريا', 'sy'                                                           => 'SYP',
            'turkey', 'türkiye', 'تركيا', 'tr'                                              => 'TRY',
            'uk', 'united kingdom', 'britain', 'great britain', 'gb'                        => 'GBP',
            'germany', 'france', 'spain', 'italy', 'netherlands',
            'belgium', 'austria', 'portugal', 'eu'                                          => 'EUR',
            default                                                                          => 'USD',
        };
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        // Alternating row colours for readability
        for ($i = 2; $i <= $lastRow; $i++) {
            $argb = ($i % 2 === 0) ? 'FFF5F4FF' : 'FFFFFFFF';
            $sheet->getStyle("A{$i}:{$lastCol}{$i}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($argb);
        }

        // Outer border on whole table
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']],
            ],
        ]);

        return [
            // Header row
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF5B4FF7']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF4D3DE3']]],
            ],
        ];
    }

    public function title(): string
    {
        return 'Tenants';
    }
}
