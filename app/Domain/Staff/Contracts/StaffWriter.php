<?php

declare(strict_types=1);

namespace App\Domain\Staff\Contracts;

use App\Models\User;

interface StaffWriter
{
    /** @param array<string, mixed> $userData */
    public function create(array $userData, array $services = [], array $schedule = []): User;

    /** @param array<string, mixed> $userData */
    public function update(User $staff, array $userData, array $services = [], array $schedule = []): bool;

    public function delete(User $staff): bool;
}
