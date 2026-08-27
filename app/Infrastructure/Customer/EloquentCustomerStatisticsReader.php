<?php

declare(strict_types=1);

namespace App\Infrastructure\Customer;

use App\Domain\Customer\Contracts\CustomerStatisticsReader;
use App\Models\Customer;

final class EloquentCustomerStatisticsReader implements CustomerStatisticsReader
{
    /** @return array<string, mixed> */
    public function getStatistics(int $customerId): array
    {
        $customer = Customer::findOrFail($customerId);
        $appointments = $customer->appointments();

        return [
            'total_appointments' => (clone $appointments)->count(),
            'completed' => (clone $appointments)->where('status', 'completed')->count(),
            'cancelled' => (clone $appointments)->where('status', 'cancelled')->count(),
            'no_show' => (clone $appointments)->where('status', 'no_show')->count(),
            'avg_rating' => round((float) (clone $appointments)->whereNotNull('rating')->avg('rating'), 1),
            'total_spent' => $customer->total_spent,
            'last_visit_at' => $customer->last_visit_at,
            'ltv_tier' => $customer->ltv_tier,
        ];
    }
}
