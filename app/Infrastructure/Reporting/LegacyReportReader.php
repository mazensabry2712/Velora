<?php

declare(strict_types=1);

namespace App\Infrastructure\Reporting;

use App\Services\ReportService;

final class LegacyReportReader
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function dashboard(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->reports->getDashboardData($period, $startDate, $endDate);
    }
}
