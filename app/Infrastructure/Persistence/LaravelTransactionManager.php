<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Shared\Contracts\TransactionManager;
use Closure;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManager
{
    public function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
