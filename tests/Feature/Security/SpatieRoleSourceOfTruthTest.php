<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class SpatieRoleSourceOfTruthTest extends TenantTestCase
{
    #[Test]
    public function tenant_users_do_not_use_a_legacy_role_id_column(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'role_id'));
    }

    #[Test]
    public function tenant_users_use_spatie_role_assignments(): void
    {
        $this->assertTrue($this->admin->hasRole($this->adminRole));
        $this->assertTrue($this->staffMember->hasRole($this->staffRole));
        $this->assertTrue($this->customer->hasRole($this->customerRole));

        $this->assertTrue($this->admin->roles()->whereKey($this->adminRole->getKey())->exists());
        $this->assertTrue($this->staffMember->roles()->whereKey($this->staffRole->getKey())->exists());
        $this->assertTrue($this->customer->roles()->whereKey($this->customerRole->getKey())->exists());
    }
}
