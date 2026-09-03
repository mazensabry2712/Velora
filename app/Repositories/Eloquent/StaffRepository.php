<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Domain\Staff\Contracts\StaffWriter;
use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class StaffRepository implements StaffRepositoryInterface
{
    public function __construct(private readonly StaffWriter $writer) {}

    public function findById(int $id): ?Staff
    {
        return Staff::find($id);
    }

    public function findWithRelations(int $id, array $relations = []): ?Staff
    {
        return Staff::with($relations)->find($id);
    }

    /** @return Collection<int, Staff> */
    public function all(): Collection
    {
        return Staff::query()
            ->with(['user', 'services', 'workingHours'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(array $staffData, array $services = [], array $schedule = []): Staff
    {
        return DB::transaction(fn (): Staff => $this->writer->create($staffData, $services, $schedule));
    }

    public function update(Staff $staff, array $staffData, array $services = [], array $schedule = []): bool
    {
        return DB::transaction(fn (): bool => $this->writer->update($staff, $staffData, $services, $schedule));
    }

    public function delete(Staff $staff): bool
    {
        return DB::transaction(fn (): bool => $this->writer->delete($staff));
    }

    /** @return Collection<int, Staff> */
    public function getBySpecialization(string $specialization): Collection
    {
        return Staff::query()
            ->where(function ($query) use ($specialization): void {
                $query->whereJsonContains('title->en', $specialization)
                    ->orWhereJsonContains('title->ar', $specialization)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('specialization', $specialization));
            })
            ->where('is_active', true)
            ->with(['user', 'services', 'workingHours'])
            ->get();
    }

    /** @return Collection<int, Staff> */
    public function getByService(int $serviceId): Collection
    {
        return Staff::query()
            ->whereHas('services', fn ($query) => $query->whereKey($serviceId))
            ->bookable()
            ->with(['user', 'services', 'workingHours'])
            ->get();
    }

    public function getSchedule(int $staffId): Collection
    {
        return Staff::findOrFail($staffId)
            ->workingHours()
            ->orderBy('day_of_week')
            ->get();
    }
}
