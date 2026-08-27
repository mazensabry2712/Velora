<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Contracts;

interface ReportReader
{
    /** @return array<string, mixed> */
    public function dashboard(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array;
}
