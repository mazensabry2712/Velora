<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Queue;
use Tests\TenantTestCase;


#[Group('feature')]
#[Group('admin')]
#[Group('queue')]
class QueueControllerTest extends TenantTestCase
{
    // ── Page views ────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_view_queue_days_listing(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.queue'));

        $response->assertOk();
        $response->assertViewIs('admin.queue.days');
    }

    #[Test]
    public function admin_can_view_queue_for_specific_date(): void
    {
        $this->actingAs($this->admin);
        $date = today()->format('Y-m-d');

        $response = $this->get(route('admin.queue.day', ['date' => $date]));

        $response->assertOk();
        $response->assertViewIs('admin.queue.index');
        $response->assertViewHas('date', $date);
    }

    #[Test]
    public function admin_can_view_print_layout_for_queue(): void
    {
        $this->actingAs($this->admin);
        $date = today()->format('Y-m-d');

        $response = $this->get(route('admin.queue.print', ['date' => $date]));

        $response->assertOk();
        $response->assertViewIs('admin.queue.print');
    }

    #[Test]
    public function guests_are_redirected_from_queue_page(): void
    {
        $response = $this->get(route('admin.queue'));

        $response->assertRedirect(route('login'));
    }

    // ── addDirect ─────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_add_customer_directly_to_queue(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.queue.add'), [
            'customer_name'  => 'Walk-in Patient',
            'customer_phone' => '0501234567',
            'staff_id'       => $this->staffMember->id,
            'service_id'     => $this->service->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('queues', ['status' => 'waiting']);
    }

    #[Test]
    public function add_direct_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.queue.add'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_name', 'customer_phone', 'staff_id', 'service_id']);
    }

    // ── callNext ─────────────────────────────────────────────────────────

    #[Test]
    public function call_next_returns_next_waiting_entry(): void
    {
        $this->actingAs($this->admin);
        $this->makeQueueEntry(['status' => 'waiting']);

        $response = $this->postJson(route('admin.api.queue.call-next'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function call_next_returns_message_when_queue_is_empty(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.queue.call-next'));

        $response->assertOk();
        $response->assertJson(['success' => false]);
    }

    // ── get single entry ──────────────────────────────────────────────────

    #[Test]
    public function admin_can_fetch_single_queue_entry(): void
    {
        $this->actingAs($this->admin);
        $queue = $this->makeQueueEntry();

        $response = $this->getJson(route('admin.api.queue.get', $queue->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.id', $queue->id);
    }

    // ── serve ─────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_start_serving_a_customer(): void
    {
        $this->actingAs($this->admin);
        $queue = $this->makeQueueEntry(['status' => 'waiting']);

        $response = $this->postJson(route('admin.api.queue.serve', $queue->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('serving', $queue->fresh()->status);
    }

    // ── complete ──────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_complete_a_queue_entry(): void
    {
        $this->actingAs($this->admin);
        $queue = $this->makeQueueEntry(['status' => 'serving']);

        $response = $this->postJson(route('admin.api.queue.complete', $queue->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('completed', $queue->fresh()->status);
    }

    // ── return to waiting ─────────────────────────────────────────────────

    #[Test]
    public function admin_can_return_entry_to_waiting_status(): void
    {
        $this->actingAs($this->admin);
        $queue = $this->makeQueueEntry(['status' => 'serving']);

        $response = $this->postJson(route('admin.api.queue.return-waiting', $queue->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('waiting', $queue->fresh()->status);
    }

    // ── setPriority ───────────────────────────────────────────────────────

    #[Test]
    public function admin_can_mark_queue_entry_as_vip(): void
    {
        $this->actingAs($this->admin);
        $queue = $this->makeQueueEntry(['is_vip' => false]);

        $response = $this->postJson(route('admin.api.queue.priority', $queue->id), [
            'priority' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertTrue((bool) $queue->fresh()->is_vip);
    }

    // ── remove ────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_remove_queue_entry(): void
    {
        $this->actingAs($this->admin);
        $queue = $this->makeQueueEntry();

        $response = $this->deleteJson(route('admin.api.queue.delete', $queue->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    // ── moveToNextDay ─────────────────────────────────────────────────────

    #[Test]
    public function admin_can_move_waiting_entry_to_next_day(): void
    {
        $this->actingAs($this->admin);
        $this->makeQueueEntry(['status' => 'waiting']);
        $date = now()->toDateString();

        $response = $this->postJson(route('admin.api.queue.move-next-day'), [
            'date'   => $date,
            'status' => 'waiting',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeQueueEntry(array $overrides = []): Queue
    {
        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => today(),
            'time_slot'   => '10:00',
            'status'      => 'confirmed',
        ]);

        return Queue::create(array_merge([
            'appointment_id' => $appt->id,
            'queue_number'   => rand(1, 999),
            'status'         => 'waiting',
            'is_vip'         => false,
        ], $overrides));
    }
}
