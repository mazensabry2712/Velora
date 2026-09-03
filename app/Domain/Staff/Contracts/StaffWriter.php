<?php

declare(strict_types=1);

namespace App\Domain\Staff\Contracts;

use App\Models\Staff;

interface StaffWriter
{
    /** @param array<string, mixed> $staffData */
    public function create(array $staffData, array $services = [], array $schedule = []): Staff;

    /** @param array<string, mixed> $staffData */
    public function update(Staff $staff, array $staffData, array $services = [], array $schedule = []): bool;

    public function delete(Staff $staff): bool;
}
