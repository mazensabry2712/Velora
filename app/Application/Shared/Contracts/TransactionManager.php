<?php

declare(strict_types=1);

namespace App\Application\Shared\Contracts;

use Closure;

interface TransactionManager
{
    /**
     * Execute the callback inside a database transaction.
     *
     * @template TReturn
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public function transaction(Closure $callback): mixed;
}
