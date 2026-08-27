<?php

declare(strict_types=1);

namespace App\Application\Reporting\Actions;

use App\Infrastructure\Reporting\LegacyReportReader;

final class GetDashboardReport
{
    public function __construct(
        private readonly LegacyReportReader $reports,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->reports->dashboard($period, $startDate, $endDate);
    }
}
