<?php

namespace Tests\Unit\Repositories;

use App\Models\Appointment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Queue;
use App\Repositories\Eloquent\AppointmentRepository;
use Tests\TenantTestCase;


#[Group('unit')]
#[Group('repositories')]
class AppointmentRepositoryTest extends TenantTestCase
{
    private AppointmentRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AppointmentRepository();
    }

    // ── findById ─────────────────────────────────────────────────────────

    #[Test]
    public function find_by_id_returns_appointment_when_found(): void
    {
        $appt = $this->makeAppointment();

        $result = $this->repo->findById($appt->id);

        $this->assertNotNull($result);
        $this->assertEquals($appt->id, $result->id);
    }

    #[Test]
    public function find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repo->findById(99999);

        $this->assertNull($result);
    }

    // ── findWithRelations ─────────────────────────────────────────────────

    #[Test]
    public function find_with_relations_loads_eager_relations(): void
    {
        $appt = $this->makeAppointment();

        $result = $this->repo->findWithRelations($appt->id, ['customer', 'staff', 'service']);

        $this->assertTrue($result->relationLoaded('customer'));
        $this->assertTrue($result->relationLoaded('staff'));
        $this->assertTrue($result->relationLoaded('service'));
    }

    // ── paginate & filters ────────────────────────────────────────────────

    #[Test]
    public function paginate_returns_all_appointments_with_no_filters(): void
    {
        $this->makeAppointment();
        $this->makeAppointment();

        $result = $this->repo->paginate([], 20);

        $this->assertGreaterThanOrEqual(2, $result->total());
    }

    #[Test]
    public function paginate_filters_by_status(): void
    {
        $this->makeAppointment(['status' => 'confirmed']);
        $this->makeAppointment(['status' => 'cancelled']);

        $confirmed = $this->repo->paginate(['status' => 'confirmed'], 20);
        $cancelled = $this->repo->paginate(['status' => 'cancelled'], 20);

        foreach ($confirmed->items() as $item) {
            $this->assertEquals('confirmed', $item->status);
        }
        foreach ($cancelled->items() as $item) {
            $this->assertEquals('cancelled', $item->status);
        }
    }

    #[Test]
    public function paginate_filters_by_staff_id(): void
    {
        $this->makeAppointment(['staff_id' => $this->staffMember->id]);
        $this->makeAppointment(['staff_id' => null]);

        $result = $this->repo->paginate(['staff_id' => $this->staffMember->id], 20);

        foreach ($result->items() as $item) {
            $this->assertEquals($this->staffMember->id, $item->staff_id);
        }
    }

    #[Test]
    public function paginate_date_filter_today_returns_only_todays_appointments(): void
    {
        $this->makeAppointment(['date' => today()]);
        $this->makeAppointment(['date' => today()->subDays(5)]);

        $result = $this->repo->paginate(['date_filter' => 'today'], 20);

        foreach ($result->items() as $item) {
            $this->assertTrue($item->date->isToday());
        }
    }

    #[Test]
    public function paginate_date_filter_custom_range_works(): void
    {
        $this->makeAppointment(['date' => '2025-01-10']);
        $this->makeAppointment(['date' => '2025-02-10']);

        $result = $this->repo->paginate([
            'date_filter' => 'custom',
            'date_from'   => '2025-01-01',
            'date_to'     => '2025-01-31',
        ], 20);

        foreach ($result->items() as $item) {
            $this->assertTrue($item->date->between('2025-01-01', '2025-01-31'));
        }
    }

    // ── countByStatus ─────────────────────────────────────────────────────

    #[Test]
    public function count_by_status_returns_correct_count(): void
    {
        $this->makeAppointment(['status' => 'pending']);
        $this->makeAppointment(['status' => 'pending']);
        $this->makeAppointment(['status' => 'confirmed']);

        $this->assertEquals(2, $this->repo->countByStatus('pending'));
        $this->assertEquals(1, $this->repo->countByStatus('confirmed'));
    }

    // ── getTodayStats ─────────────────────────────────────────────────────

    #[Test]
    public function get_today_stats_returns_correct_keys_and_counts(): void
    {
        $this->makeAppointment(['date' => today(), 'status' => 'confirmed']);
        $this->makeAppointment(['date' => today(), 'status' => 'pending']);
        $this->makeAppointment(['date' => today()->subDay(), 'status' => 'confirmed']); // should not be counted

        $stats = $this->repo->getTodayStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('confirmed', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('cancelled', $stats);
        $this->assertArrayHasKey('in_queue', $stats);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['confirmed']);
        $this->assertEquals(1, $stats['pending']);
    }

    // ── getWeeklyStats ────────────────────────────────────────────────────

    #[Test]
    public function get_weekly_stats_returns_this_week_and_last_week(): void
    {
        $this->makeAppointment(['date' => now()->startOfWeek()->addDay()]);
        $this->makeAppointment(['date' => now()->subWeek()->startOfWeek()->addDay()]);

        $stats = $this->repo->getWeeklyStats();

        $this->assertArrayHasKey('this_week', $stats);
        $this->assertArrayHasKey('last_week', $stats);
        $this->assertArrayHasKey('percentage_change', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['this_week']);
        $this->assertGreaterThanOrEqual(1, $stats['last_week']);
    }

    // ── CRUD ─────────────────────────────────────────────────────────────

    #[Test]
    public function create_persists_appointment_to_database(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => today()->addDay(),
            'time_slot'   => '10:00',
            'status'      => 'pending',
        ];

        $appt = $this->repo->create($data);

        $this->assertInstanceOf(Appointment::class, $appt);
        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'status' => 'pending']);
    }

    #[Test]
    public function update_changes_appointment_fields(): void
    {
        $appt = $this->makeAppointment(['status' => 'pending']);

        $result = $this->repo->update($appt, ['status' => 'confirmed']);

        $this->assertTrue($result);
        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'status' => 'confirmed']);
    }

    #[Test]
    public function delete_soft_deletes_the_appointment(): void
    {
        $appt = $this->makeAppointment();

        $this->repo->delete($appt);

        $this->assertSoftDeleted('appointments', ['id' => $appt->id]);
    }

    // ── getByDate ─────────────────────────────────────────────────────────

    #[Test]
    public function get_by_date_returns_only_appointments_for_that_date(): void
    {
        $this->makeAppointment(['date' => '2025-06-01']);
        $this->makeAppointment(['date' => '2025-06-02']);

        $results = $this->repo->getByDate('2025-06-01');

        $this->assertCount(1, $results);
        $this->assertEquals('2025-06-01', $results->first()->date->format('Y-m-d'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeAppointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => today()->addDay(),
            'time_slot'   => '10:00',
            'status'      => 'pending',
        ], $overrides));
    }
}
