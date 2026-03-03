<?php

namespace Tests\Unit\Repositories;

use App\Models\Appointment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Queue;
use App\Repositories\Eloquent\QueueRepository;
use Tests\TenantTestCase;


#[Group('unit')]
#[Group('repositories')]
class QueueRepositoryTest extends TenantTestCase
{
    private QueueRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new QueueRepository();
    }

    // ── findById ─────────────────────────────────────────────────────────

    #[Test]
    public function find_by_id_returns_queue_entry(): void
    {
        $queue = $this->makeQueueEntry();

        $result = $this->repo->findById($queue->id);

        $this->assertNotNull($result);
        $this->assertEquals($queue->id, $result->id);
    }

    #[Test]
    public function find_by_id_returns_null_when_missing(): void
    {
        $this->assertNull($this->repo->findById(99999));
    }

    // ── getByDate ─────────────────────────────────────────────────────────

    #[Test]
    public function get_by_date_returns_entries_for_that_date(): void
    {
        $queue = $this->makeQueueEntry();

        $results = $this->repo->getByDate(now()->toDateString());

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    #[Test]
    public function get_by_date_orders_vip_first(): void
    {
        $regular = $this->makeQueueEntry(['is_vip' => false]);
        $vip     = $this->makeQueueEntry(['is_vip' => true]);

        $results = $this->repo->getByDate(now()->toDateString());

        $this->assertTrue((bool) $results->first()->is_vip);
    }

    // ── getActive ─────────────────────────────────────────────────────────

    #[Test]
    public function get_active_returns_only_waiting_and_serving(): void
    {
        $this->makeQueueEntry(['status' => 'waiting']);
        $this->makeQueueEntry(['status' => 'serving']);
        $this->makeQueueEntry(['status' => 'completed']);

        $active = $this->repo->getActive();

        foreach ($active as $item) {
            $this->assertContains($item->status, ['waiting', 'serving']);
        }
    }

    // ── callNext (VIP priority) ───────────────────────────────────────────

    #[Test]
    public function call_next_returns_null_when_queue_is_empty(): void
    {
        $this->assertNull($this->repo->callNext());
    }

    #[Test]
    public function call_next_picks_vip_before_regular(): void
    {
        $regular = $this->makeQueueEntry(['is_vip' => false, 'status' => 'waiting']);
        $vip     = $this->makeQueueEntry(['is_vip' => true,  'status' => 'waiting']);

        $called = $this->repo->callNext();

        $this->assertNotNull($called);
        $this->assertEquals($vip->id, $called->id);
        $this->assertEquals('serving', $called->fresh()->status);
    }

    #[Test]
    public function call_next_updates_status_to_serving(): void
    {
        $queue = $this->makeQueueEntry(['status' => 'waiting']);

        $called = $this->repo->callNext();

        $this->assertEquals($queue->id, $called->id);
        $this->assertEquals('serving', Queue::find($queue->id)->status);
    }

    // ── CRUD ─────────────────────────────────────────────────────────────

    #[Test]
    public function create_persists_queue_entry(): void
    {
        $appt = $this->makeAppointment();

        $queue = $this->repo->create([
            'appointment_id' => $appt->id,
            'queue_number'   => '001',
            'status'         => 'waiting',
        ]);

        $this->assertDatabaseHas('queues', ['id' => $queue->id, 'status' => 'waiting']);
    }

    #[Test]
    public function update_changes_queue_fields(): void
    {
        $queue = $this->makeQueueEntry(['status' => 'waiting']);

        $result = $this->repo->update($queue, ['counter_number' => '3']);

        $this->assertTrue($result);
        $this->assertDatabaseHas('queues', ['id' => $queue->id, 'counter_number' => '3']);
    }

    #[Test]
    public function delete_removes_queue_entry(): void
    {
        $queue = $this->makeQueueEntry();

        $this->repo->delete($queue);

        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    // ── getDailyStats ─────────────────────────────────────────────────────

    #[Test]
    public function get_daily_stats_returns_correct_structure(): void
    {
        $this->makeQueueEntry(['status' => 'waiting']);
        $this->makeQueueEntry(['status' => 'completed']);

        $stats = $this->repo->getDailyStats(now()->toDateString());

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('waiting', $stats);
        $this->assertArrayHasKey('serving', $stats);
        $this->assertArrayHasKey('completed', $stats);
    }

    // ── getOverallStats ───────────────────────────────────────────────────

    #[Test]
    public function get_overall_stats_returns_correct_keys(): void
    {
        $stats = $this->repo->getOverallStats();

        $this->assertArrayHasKey('waiting', $stats);
        $this->assertArrayHasKey('serving', $stats);
        $this->assertArrayHasKey('completed', $stats);
    }

    // ── moveToNextDay ─────────────────────────────────────────────────────

    #[Test]
    public function move_to_next_day_changes_queue_date(): void
    {
        $date = now()->toDateString();
        $this->makeQueueEntry(['status' => 'waiting']);

        $count = $this->repo->moveToNextDay($date, 'waiting');

        $this->assertGreaterThanOrEqual(1, $count);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeQueueEntry(array $overrides = []): Queue
    {
        $appt = $this->makeAppointment();

        return Queue::create(array_merge([
            'appointment_id' => $appt->id,
            'queue_number'   => rand(1, 999),
            'status'         => 'waiting',
            'is_vip'         => false,
        ], $overrides));
    }

    private function makeAppointment(): Appointment
    {
        return Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => today(),
            'time_slot'   => '10:00',
            'status'      => 'confirmed',
        ]);
    }
}
