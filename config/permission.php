<?php

use App\Models\Role;
use Spatie\Permission\DefaultTeamResolver;
use Spatie\Permission\Models\Permission;

return [

    'models' => [
        'permission' => Permission::class,
        'role' => Role::class,
        'team_resolver' => DefaultTeamResolver::class,
        'guard_names' => [
            'web',
        ],
        'cache' => [
            'expiration_time' => \DateInterval::createFromDateString('24 hours'),
            'key' => 'spatie.permission.cache',
            'store' => 'default',
        ],
        'register_permission_check_method' => true,
        'events' => false,
        'teams' => false,
        'display_permission_in_exception' => false,
        'enable_wildcard_permission' => false,
        'permission_loader' => Spatie\Permission\PermissionRegistrar::class,
        'show_role_in_exception' => false,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null,
        'permission_pivot_key' => null,
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    'teams' => false,

    'team_resolver' => DefaultTeamResolver::class,

    'use_test_mode' => false,
];
