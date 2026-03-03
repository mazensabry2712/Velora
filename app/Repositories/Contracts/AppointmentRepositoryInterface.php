<?php

namespace App\Repositories\Contracts;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AppointmentRepositoryInterface
{
    public function findById(int $id): ?Appointment;

    public function findWithRelations(int $id, array $relations = []): ?Appointment;

    /** @return LengthAwarePaginator<Appointment> */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** @return Collection<int, Appointment> */
    public function getByDate(string $date): Collection;

    /** @return Collection<int, Appointment> */
    public function getByCustomer(int $customerId): Collection;

    /** @return Collection<int, Appointment> */
    public function getByStaff(int $staffId): Collection;

    public function create(array $data): Appointment;

    public function update(Appointment $appointment, array $data): bool;

    public function delete(Appointment $appointment): bool;

    public function countByStatus(string $status, ?string $date = null): int;

    public function getTodayStats(): array;

    public function getWeeklyStats(): array;
}
