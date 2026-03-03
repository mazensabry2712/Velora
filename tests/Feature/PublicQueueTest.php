<?php

namespace Tests\Feature;

use Tests\TenantTestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\Setting;

/**
 * Tests for the public queue display page and API.
 *
 * Uses TenantTestCase (tenant-scoped, transactions per test).
 */
class PublicQueueTest extends TenantTestCase
{
    // ── Helpers ──────────────────────────────────────────────────────────

    private function makeAppointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'customer_id' => $this->customer->id,
            'date'        => now()->format('Y-m-d'),
            'time_slot'   => '10:00',
            'status'      => 'confirmed',
        ], $overrides));
    }

    private function makeQueue(Appointment $appointment, array $overrides = []): Queue
    {
        return Queue::create(array_merge([
            'appointment_id' => $appointment->id,
            'queue_number'   => 1,
            'queue_date'     => today()->format('Y-m-d'),
            'status'         => 'waiting',
            'is_vip'         => false,
        ], $overrides));
    }

    // ── Tests ─────────────────────────────────────────────────────────────

    #[Test]
    public function queue_status_page_loads_successfully(): void
    {
        $response = $this->get('/queue/status');

        $response->assertStatus(200);
        $response->assertViewIs('customer.queue-status');
    }

    #[Test]
    public function queue_route_redirects_to_status_page(): void
    {
        $response = $this->get('/queue');

        $response->assertRedirect('/queue/status');
    }

    #[Test]
    public function queue_status_page_has_link_to_booking(): void
    {
        $response = $this->get('/queue/status');

        $response->assertStatus(200);
        $response->assertSee('/book');
    }

    #[Test]
    public function public_queue_api_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => ['current', 'queues', 'total_waiting'],
                 ]);
    }

    #[Test]
    public function public_queue_api_shows_current_serving(): void
    {
        $appointment = $this->makeAppointment();
        $this->makeQueue($appointment, ['queue_number' => 1, 'status' => 'serving']);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
                 ->assertJsonPath('data.current.queue_number', '1');
    }

    #[Test]
    public function public_queue_api_shows_waiting_queues(): void
    {
        $app1 = $this->makeAppointment(['time_slot' => '10:00']);
        $app2 = $this->makeAppointment(['time_slot' => '10:30']);

        $this->makeQueue($app1, ['queue_number' => 2, 'status' => 'waiting']);
        $this->makeQueue($app2, ['queue_number' => 3, 'status' => 'waiting']);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
                 ->assertJsonPath('data.total_waiting', 2);
    }

    #[Test]
    public function public_queue_api_returns_null_when_no_one_serving(): void
    {
        $appointment = $this->makeAppointment();
        $this->makeQueue($appointment, ['queue_number' => 1, 'status' => 'waiting']);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
                 ->assertJsonPath('data.current', null);
    }

    #[Test]
    public function public_queue_api_does_not_expose_customer_names(): void
    {
        $appointment = $this->makeAppointment();
        $this->makeQueue($appointment, ['queue_number' => 5, 'status' => 'waiting']);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
                 ->assertDontSee($this->customer->name);
    }

    #[Test]
    public function public_queue_api_only_returns_safe_fields_for_waiting(): void
    {
        $appointment = $this->makeAppointment();
        $this->makeQueue($appointment, ['queue_number' => 10, 'status' => 'waiting', 'is_vip' => true]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200);

        foreach ($response->json('data.queues') as $queue) {
            $this->assertArrayHasKey('queue_number', $queue);
            $this->assertArrayHasKey('status', $queue);
            $this->assertArrayNotHasKey('is_vip', $queue);
            $this->assertArrayNotHasKey('customer_name', $queue);
        }
    }

    #[Test]
    public function public_queue_api_filters_by_today_only(): void
    {
        // Today queue
        $appToday = $this->makeAppointment();
        $this->makeQueue($appToday, [
            'queue_number' => 1,
            'queue_date'   => today()->format('Y-m-d'),
            'status'       => 'waiting',
        ]);

        // Yesterday queue — should NOT appear
        $appYesterday = $this->makeAppointment(['date' => now()->subDay()->format('Y-m-d')]);
        $this->makeQueue($appYesterday, [
            'queue_number' => 100,
            'queue_date'   => today()->subDay()->format('Y-m-d'),
            'status'       => 'waiting',
        ]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
                 ->assertJsonPath('data.total_waiting', 1);
    }
}
