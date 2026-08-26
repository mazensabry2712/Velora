<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Application role model.
 *
 * The application uses Spatie Laravel Permission for RBAC. This thin wrapper
 * keeps the historic App\Models\Role namespace available to application/test
 * code while inheriting the package's complete Role implementation.
 */
class Role extends \Spatie\Permission\Models\Role
{
}
