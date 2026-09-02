<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Domain\Staff\Contracts\StaffWriter;
use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class StaffRepository implements StaffRepositoryInterface
{
    public function __construct(private readonly StaffWriter $writer) {}

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findWithRelations(int $id, array $relations = []): ?User
    {
        return User::with($relations)->findOrFail($id);
    }

    public function all(): Collection
    {
        return User::role('Staff')
            ->with(['staffProfile', 'activeSchedules'])
            ->get();
    }

    public function create(array $userData, array $services = [], array $schedule = []): User
    {
        return DB::transaction(fn (): User => $this->writer->create($userData, $services, $schedule));
    }

    public function update(User $staff, array $userData, array $services = [], array $schedule = []): bool
    {
        return DB::transaction(fn (): bool => $this->writer->update($staff, $userData, $services, $schedule));
    }

    public function delete(User $staff): bool
    {
        return DB::transaction(fn (): bool => $this->writer->delete($staff));
    }

    public function getBySpecialization(string $specialization): Collection
    {
        return User::where('specialization', $specialization)
            ->role('Staff')
            ->with('staffProfile')
            ->get(['id', 'name', 'specialization']);
    }

    public function getByService(int $serviceId): Collection
    {
        return User::whereHas('staffProfile.services', fn ($q) => $q->where('services.id', $serviceId))
            ->role('Staff')
            ->whereHas('staffProfile', fn ($q) => $q->where('is_active', true)->where('accepts_bookings', true))
            ->with(['activeSchedules', 'staffProfile'])
            ->get(['id', 'name']);
    }

    public function getSchedule(int $staffId): Collection
    {
        return User::findOrFail($staffId)
            ->activeSchedules()
            ->orderBy('day_of_week')
            ->get();
    }
}
