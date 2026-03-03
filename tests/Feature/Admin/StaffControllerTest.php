<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;


#[Group('feature')]
#[Group('admin')]
#[Group('staff')]
class StaffControllerTest extends TenantTestCase
{
    // ── Page view ─────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_view_staff_management_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.staff'));

        $response->assertOk();
        $response->assertViewIs('admin.staff.index');
        $response->assertViewHas('staffMembers');
        $response->assertViewHas('services');
    }

    #[Test]
    public function guests_cannot_access_staff_page(): void
    {
        $response = $this->get(route('admin.staff'));

        $response->assertRedirect(route('login'));
    }

    // ── show ──────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_fetch_staff_member_details(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson(route('admin.api.staff.show', $this->staffMember->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.id', $this->staffMember->id);
    }

    #[Test]
    public function show_returns_404_for_missing_staff(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson(route('admin.api.staff.show', 99999));

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    // ── store ─────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_new_staff_member(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. New Staff',
            'email'          => 'newstaff@clinic.com',
            'phone'          => '0501234567',
            'specialization' => 'Cardiology',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['email' => 'newstaff@clinic.com']);
    }

    #[Test]
    public function store_requires_name_email_and_specialization(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'specialization']);
    }

    #[Test]
    public function store_rejects_duplicate_email(): void
    {
        $this->actingAs($this->admin);

        // Try to create staff with the same email as the existing staff member
        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Duplicate Email',
            'email'          => $this->staffMember->email,
            'specialization' => 'Cardiology',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function store_returns_default_password_in_response(): void
    {
        $this->actingAs($this->admin);

        $email    = 'drpassword@clinic.com';
        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. Password',
            'email'          => $email,
            'specialization' => 'Ortho',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['default_password']);
        $this->assertStringContainsString('drpassword', $response->json('default_password'));
    }

    // ── update ────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_update_staff_member(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson(route('admin.api.staff.update', $this->staffMember->id), [
            'name'           => 'Updated Name',
            'email'          => $this->staffMember->email,
            'specialization' => 'Pediatrics',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', [
            'id'             => $this->staffMember->id,
            'specialization' => 'Pediatrics',
        ]);
    }

    #[Test]
    public function update_allows_same_email_for_the_same_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson(route('admin.api.staff.update', $this->staffMember->id), [
            'name'           => 'Same Email Update',
            'email'          => $this->staffMember->email, // same email, should be fine
            'specialization' => 'ENT',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── destroy ───────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_delete_staff_member(): void
    {
        $this->actingAs($this->admin);
        $newStaff = User::create([
            'name'     => 'Temp Staff',
            'email'    => 'tempstaff@clinic.com',
            'password' => bcrypt('password'),
            'role_id'  => $this->staffRole->id,
        ]);

        $response = $this->deleteJson(route('admin.api.staff.destroy', $newStaff->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── bySpecialization ──────────────────────────────────────────────────

    #[Test]
    public function admin_can_filter_staff_by_specialization(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson(route('admin.api.staff.by-specialization', 'General'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['data']);
    }

    // ── services ──────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_get_services_assigned_to_staff_member(): void
    {
        $this->actingAs($this->admin);
        $this->staffMember->services()->sync([$this->service->id]);

        $response = $this->getJson(route('admin.api.staff.services', $this->staffMember->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
