<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findWithRelations(int $id, array $relations = []): ?User;

    /** @return Collection<int, User> */
    public function all(): Collection;

    public function create(array $userData, array $services = [], array $schedule = []): User;

    public function update(User $staff, array $userData, array $services = [], array $schedule = []): bool;

    public function delete(User $staff): bool;

    /** @return Collection<int, User> */
    public function getBySpecialization(string $specialization): Collection;

    /** @return Collection<int, User> */
    public function getByService(int $serviceId): Collection;

    public function getSchedule(int $staffId): Collection;
}
