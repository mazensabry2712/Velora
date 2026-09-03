<?php

namespace App\Repositories\Eloquent;

use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AppointmentRepository implements AppointmentRepositoryInterface
{
    public function findById(int $id): ?Appointment
    {
        return Appointment::find($id);
    }

    public function findWithRelations(int $id, array $relations = []): ?Appointment
    {
        return Appointment::with($relations)->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Appointment::with(['customer', 'staff', 'service', 'queue']);

        if (! empty($filters['date_filter'])) {
            match ($filters['date_filter']) {
                'today' => $query->whereDate('starts_at', today()),
                'week' => $query->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereMonth('starts_at', now()->month)->whereYear('starts_at', now()->year),
                'custom' => (function () use ($query, $filters): void {
                    if (! empty($filters['date_from'])) {
                        $query->whereDate('starts_at', '>=', $filters['date_from']);
                    }
                    if (! empty($filters['date_to'])) {
                        $query->whereDate('starts_at', '<=', $filters['date_to']);
                    }
                })(),
                default => null,
            };
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['staff_id'])) {
            $query->where('staff_id_new', $filters['staff_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('customer', fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $allowedSorts = ['starts_at', 'status', 'created_at', 'id'];
        $sortBy = in_array($filters['sort'] ?? 'starts_at', $allowedSorts, true) ? ($filters['sort'] ?? 'starts_at') : 'starts_at';
        $sortDir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir)->orderBy('id');

        return $query->paginate($perPage)->withQueryString();
    }

    public function getByDate(string $date): Collection
    {
        return Appointment::with(['customer', 'staff', 'service', 'queue'])
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get();
    }

    public function getByCustomer(int $customerId): Collection
    {
        return Appointment::with(['staff', 'service'])
            ->where('customer_id_new', $customerId)
            ->orderByDesc('starts_at')
            ->get();
    }

    public function getByStaff(int $staffId): Collection
    {
        return Appointment::with(['customer', 'service'])
            ->where('staff_id_new', $staffId)
            ->orderByDesc('starts_at')
            ->get();
    }

    public function create(array $data): Appointment
    {
        return Appointment::create($data);
    }

    public function update(Appointment $appointment, array $data): bool
    {
        return $appointment->update($data);
    }

    public function delete(Appointment $appointment): bool
    {
        return (bool) $appointment->delete();
    }

    public function countByStatus(string $status, ?string $date = null): int
    {
        $query = Appointment::where('status', $status);

        if ($date) {
            $query->whereDate('starts_at', $date);
        }

        return $query->count();
    }

    public function getTodayStats(): array
    {
        $today = today();

        return [
            'total' => Appointment::whereDate('starts_at', $today)->count(),
            'confirmed' => Appointment::whereDate('starts_at', $today)->where('status', 'confirmed')->count(),
            'pending' => Appointment::whereDate('starts_at', $today)->where('status', 'pending')->count(),
            'completed' => Appointment::whereDate('starts_at', $today)->where('status', 'completed')->count(),
            'cancelled' => Appointment::whereDate('starts_at', $today)->where('status', 'cancelled')->count(),
            'in_queue' => Appointment::whereDate('starts_at', $today)
                ->whereHas('queue', fn ($q) => $q->whereIn('status', ['waiting', 'serving']))
                ->count(),
        ];
    }

    public function getWeeklyStats(): array
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();
        $lastStart = now()->subWeek()->startOfWeek();
        $lastEnd = now()->subWeek()->endOfWeek();

        $thisWeek = Appointment::whereBetween('starts_at', [$start, $end])->count();
        $lastWeek = Appointment::whereBetween('starts_at', [$lastStart, $lastEnd])->count();
        $change = $lastWeek > 0
            ? round((($thisWeek - $lastWeek) / $lastWeek) * 100)
            : ($thisWeek > 0 ? 100 : 0);

        return [
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
            'percentage_change' => $change,
        ];
    }
}
