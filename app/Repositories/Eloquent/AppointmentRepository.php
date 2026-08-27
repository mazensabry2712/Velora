<?php

namespace App\Repositories\Eloquent;

use App\Domain\Booking\Contracts\AppointmentCommand;
use App\Models\Appointment;
use App\Models\Queue;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AppointmentRepository implements AppointmentRepositoryInterface, AppointmentCommand
{
    public function findById(int $id): ?Appointment
    {
        return Appointment::find($id);
    }

    public function findWithRelations(int $id, array $relations = []): ?Appointment
    {
        return Appointment::with($relations)->findOrFail($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Appointment::with(['customer', 'staff', 'service', 'queue']);

        if (!empty($filters['date_filter'])) {
            match ($filters['date_filter']) {
                'today'  => $query->whereDate('date', today()),
                'week'   => $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
                'month'  => $query->whereMonth('date', now()->month)->whereYear('date', now()->year),
                'custom' => (function () use ($query, $filters) {
                    if (!empty($filters['date_from'])) {
                        $query->whereDate('date', '>=', $filters['date_from']);
                    }
                    if (!empty($filters['date_to'])) {
                        $query->whereDate('date', '<=', $filters['date_to']);
                    }
                })(),
                default  => null,
            };
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('customer', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $sortBy  = $filters['sort'] ?? 'date';
        $sortDir = $filters['dir'] ?? 'desc';

        $query->orderBy($sortBy, $sortDir)->orderBy('time_slot')->orderBy('id');

        return $query->paginate($perPage)->withQueryString();
    }

    public function getByDate(string $date): Collection
    {
        return Appointment::with(['customer', 'staff', 'service', 'queue'])
            ->whereDate('date', $date)
            ->orderBy('time_slot')
            ->get();
    }

    public function getByCustomer(int $customerId): Collection
    {
        return Appointment::with(['staff', 'service'])
            ->where('customer_id', $customerId)
            ->orderByDesc('date')
            ->get();
    }

    public function getByStaff(int $staffId): Collection
    {
        return Appointment::with(['customer', 'service'])
            ->where('staff_id', $staffId)
            ->orderByDesc('date')
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
            $query->whereDate('date', $date);
        }

        return $query->count();
    }

    public function getTodayStats(): array
    {
        $today = today()->format('Y-m-d');

        return [
            'total'     => Appointment::whereDate('date', $today)->count(),
            'confirmed' => Appointment::whereDate('date', $today)->where('status', 'confirmed')->count(),
            'pending'   => Appointment::whereDate('date', $today)->where('status', 'pending')->count(),
            'completed' => Appointment::whereDate('date', $today)->where('status', 'completed')->count(),
            'cancelled' => Appointment::whereDate('date', $today)->where('status', 'cancelled')->count(),
            'in_queue'  => Appointment::whereHas('queue', fn ($q) => $q->whereIn('status', ['waiting', 'serving']))->count(),
        ];
    }

    public function getWeeklyStats(): array
    {
        $start = now()->startOfWeek()->format('Y-m-d');
        $end   = now()->endOfWeek()->format('Y-m-d');
        $lastStart = now()->subWeek()->startOfWeek()->format('Y-m-d');
        $lastEnd   = now()->subWeek()->endOfWeek()->format('Y-m-d');

        $thisWeek = Appointment::whereBetween('date', [$start, $end])->count();
        $lastWeek = Appointment::whereBetween('date', [$lastStart, $lastEnd])->count();

        $change = $lastWeek > 0
            ? round((($thisWeek - $lastWeek) / $lastWeek) * 100)
            : ($thisWeek > 0 ? 100 : 0);

        return [
            'this_week'         => $thisWeek,
            'last_week'         => $lastWeek,
            'percentage_change' => $change,
        ];
    }
}
