<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    public function findById(int $id): ?Staff;

    public function findWithRelations(int $id, array $relations = []): ?Staff;

    /** @return Collection<int, Staff> */
    public function all(): Collection;

    public function create(array $staffData, array $services = [], array $schedule = []): Staff;

    public function update(Staff $staff, array $staffData, array $services = [], array $schedule = []): bool;

    public function delete(Staff $staff): bool;

    /** @return Collection<int, Staff> */
    public function getBySpecialization(string $specialization): Collection;

    /** @return Collection<int, Staff> */
    public function getByService(int $serviceId): Collection;

    public function getSchedule(int $staffId): Collection;
}
