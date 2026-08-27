<?php

declare(strict_types=1);

namespace App\Infrastructure\Staff;

use App\Domain\Staff\Contracts\ServiceReader;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

final class EloquentServiceReader implements ServiceReader
{
    /** @return Collection<int, Service> */
    public function allOrderedByName(): Collection
    {
        return Service::query()->orderBy('name')->get();
    }
}
