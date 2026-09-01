<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('security')]
final class AuthorizationMatrixExpandedScenarioTest extends TenantTestCase
{
    #[Test]
    public function staff_cannot_create_or_delete_staff_accounts(): void
    {
        $this->actingAs($this->staffMember);

        $this->postJson(route('admin.api.staff.store'), [
            'name' => 'Unauthorized Staff',
            'email' => 'qa-unauthorized-' . uniqid() . '@example.com',
            'specialization' => 'Reception',
            'phone' => '+201000000007',
        ])->assertForbidden();

        $this->deleteJson(route('admin.api.staff.destroy', ['id' => $this->staff->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $this->staffMember->id]);
    }

    #[Test]
    public function assistant_cannot_change_schedule_configuration(): void
    {
        $assistantRole = \App\Models\Role::firstOrCreate(['name' => 'Assistant']);
        $assistant = \App\Models\User::create([
            'name' => 'QA Schedule Assistant',
            'email' => 'qa-schedule-assistant-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'specialization' => 'Reception',
        ]);
        $assistant->assignRole($assistantRole);

        $this->actingAs($assistant);

        $this->postJson(route('admin.api.timeslots.store'), [
            'start_time' => '09:00',
            'end_time' => '10:00',
        ])->assertForbidden();

        $this->postJson(route('admin.api.workingdays.toggle', ['id' => 1]), [
            'is_active' => false,
        ])->assertForbidden();
    }

    #[Test]
    public function tenant_admin_can_manage_staff_and_schedule_configuration(): void
    {
        $this->actingAs($this->admin);

        $this->postJson(route('admin.api.timeslots.store'), [
            'start_time' => '14:00',
            'end_time' => '15:00',
        ])->assertOk();

        $this->postJson(route('admin.api.staff.store'), [
            'name' => 'Authorized Staff',
            'email' => 'qa-authorized-' . uniqid() . '@example.com',
            'specialization' => 'Reception',
            'phone' => '+201000000008',
        ])->assertOk();
    }
}
