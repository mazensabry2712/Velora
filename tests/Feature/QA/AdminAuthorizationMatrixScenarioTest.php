<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('security')]
final class AdminAuthorizationMatrixScenarioTest extends TenantTestCase
{
    #[Test]
    public function staff_cannot_mutate_services_but_can_read_them(): void
    {
        $this->actingAs($this->staffMember);

        $this->getJson(route('admin.api.services.show', ['id' => $this->service->id]))
            ->assertOk()
            ->assertJsonPath('data.id', $this->service->id);

        $this->postJson(route('admin.api.services.store'), [
            'name' => 'Unauthorized Service',
            'duration' => 30,
            'price' => 50,
            'is_active' => true,
        ])->assertForbidden();
    }

    #[Test]
    public function assistant_cannot_mutate_settings_or_services(): void
    {
        $assistantRole = Role::firstOrCreate(['name' => 'Assistant']);
        $assistant = User::create([
            'name' => 'QA Assistant',
            'email' => 'qa-assistant-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'specialization' => 'Reception',
        ]);
        $assistant->assignRole($assistantRole);

        $this->actingAs($assistant);

        $this->postJson(route('admin.api.settings.save'), [
            'business_name' => 'Unauthorized Update',
        ])->assertForbidden();

        $this->postJson(route('admin.api.services.store'), [
            'name' => 'Unauthorized Assistant Service',
            'duration' => 30,
            'price' => 50,
            'is_active' => true,
        ])->assertForbidden();
    }

    #[Test]
    public function tenant_admin_can_mutate_services(): void
    {
        $this->actingAs($this->admin);

        $this->postJson(route('admin.api.services.store'), [
            'name' => 'Authorized Service',
            'duration' => 30,
            'price' => 75,
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Authorized Service');
    }
}
