<?php

declare(strict_types=1);

namespace App\Domain\Staff\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ServiceReader
{
    /** @return Collection<int, mixed> */
    public function allOrderedByName(): Collection;
}
