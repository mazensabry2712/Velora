<?php

declare(strict_types=1);

namespace App\Domain\Staff\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface StaffReader
{
    public function findById(int $id): ?User;

    public function findWithRelations(int $id, array $relations = []): ?User;

    /** @return Collection<int, User> */
    public function all(): Collection;

    /** @return Collection<int, User> */
    public function getBySpecialization(string $specialization): Collection;

    /** @return Collection<int, User> */
    public function getByService(int $serviceId): Collection;

    /** @return Collection<int, mixed> */
    public function getSchedule(int $staffId): Collection;
}
