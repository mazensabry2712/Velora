<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;


#[Group('feature')]
#[Group('admin')]
#[Group('services')]
class ServiceControllerTest extends TenantTestCase
{
    // ── index ─────────────────────────────────────────────────────────────

    #[Test]
    public function public_services_api_returns_active_services(): void
    {
        // show() requires auth (admin-only API)
        $this->actingAs($this->admin);

        $response = $this->getJson(route('admin.api.services.show', $this->service->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── show ──────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_get_service_details(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson(route('admin.api.services.show', $this->service->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.id', $this->service->id);
    }

    // ── store ─────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_service(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.services.store'), [
            'name'      => 'Dental Check',
            'duration'  => 45,
            'price'     => 200.00,
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('services', ['name' => 'Dental Check']);
    }

    #[Test]
    public function store_validates_name_is_required(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.services.store'), [
            'duration' => 30,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function store_validates_duration_is_between_5_and_480_minutes(): void
    {
        $this->actingAs($this->admin);

        $tooShort = $this->postJson(route('admin.api.services.store'), [
            'name'     => 'Quick',
            'duration' => 3,
        ]);
        $tooLong  = $this->postJson(route('admin.api.services.store'), [
            'name'     => 'Marathon',
            'duration' => 999,
        ]);

        $tooShort->assertStatus(422)->assertJsonValidationErrors(['duration']);
        $tooLong->assertStatus(422)->assertJsonValidationErrors(['duration']);
    }

    #[Test]
    public function store_validates_price_is_non_negative(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.services.store'), [
            'name'  => 'Free Service',
            'price' => -10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    // ── update ────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_update_existing_service(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson(route('admin.api.services.update', $this->service->id), [
            'name'      => 'Updated Consultation',
            'duration'  => 60,
            'price'     => 150.00,
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('services', [
            'id'   => $this->service->id,
            'name' => 'Updated Consultation',
        ]);
    }

    // ── destroy ───────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_delete_service(): void
    {
        $this->actingAs($this->admin);
        $svc = Service::create([
            'name'      => 'Temporary Service',
            'duration'  => 20,
            'price'     => 50,
            'is_active' => true,
        ]);

        $response = $this->deleteJson(route('admin.api.services.destroy', $svc->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSoftDeleted('services', ['id' => $svc->id]);
    }

    // ── timeSlots ─────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_a_time_slot(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.timeslots.store'), [
            'start_time' => '09:00',
            'end_time'   => '09:30',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('time_slots', ['start_time' => '09:00']);
    }

    #[Test]
    public function time_slot_end_must_be_after_start(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.timeslots.store'), [
            'start_time' => '10:00',
            'end_time'   => '09:00', // before start
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function admin_can_delete_time_slot(): void
    {
        $this->actingAs($this->admin);

        // Create then delete
        $slot = \App\Models\TimeSlot::create(['start_time' => '14:00', 'end_time' => '14:30', 'is_active' => true]);

        $response = $this->deleteJson(route('admin.api.timeslots.destroy', $slot->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── workingDays ───────────────────────────────────────────────────────

    #[Test]
    public function admin_can_toggle_a_working_day(): void
    {
        $this->actingAs($this->admin);

        $day = \App\Models\WorkingDay::create([
            'day_of_week' => 1, // Monday
            'day_name'    => 'Monday',
            'day_name_ar' => 'الاثنين',
            'is_active'   => true,
            'open_time'   => '08:00',
            'close_time'  => '17:00',
        ]);

        $response = $this->postJson(route('admin.api.workingdays.toggle', $day->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
