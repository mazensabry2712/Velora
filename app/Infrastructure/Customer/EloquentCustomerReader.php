<?php

declare(strict_types=1);

namespace App\Infrastructure\Customer;

use App\Domain\Customer\Contracts\CustomerReader;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCustomerReader implements CustomerReader
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Customer::withCount('appointments');

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (($filters['ltv_tier'] ?? null) !== null && $filters['ltv_tier'] !== '') {
            $query->where('ltv_tier', $filters['ltv_tier']);
        }

        if (($filters['is_blocked'] ?? null) !== null && $filters['is_blocked'] !== '') {
            $query->where('is_blocked', (bool) $filters['is_blocked']);
        }

        if (($filters['tag'] ?? null) !== null && $filters['tag'] !== '') {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        if (($filters['acquisition_source'] ?? null) !== null && $filters['acquisition_source'] !== '') {
            $query->where('acquisition_source', $filters['acquisition_source']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findWithStats(int $customerId): Customer
    {
        return Customer::withCount('appointments')->findOrFail($customerId);
    }

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

    public function paginateAppointments(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        $customer = Customer::findOrFail($customerId);

        return $customer->appointments()
            ->with(['service:id,name,price', 'staff:id,first_name,last_name'])
            ->orderByDesc('starts_at')
            ->paginate($perPage);
    }
}
