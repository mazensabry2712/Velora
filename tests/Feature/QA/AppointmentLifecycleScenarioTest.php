<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Application\Booking\Actions\ChangeAppointmentStatus;
use App\Domain\Booking\Rules\AppointmentStatusTransition;
use App\Models\Appointment;
use App\Models\Queue;
use DomainException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class AppointmentLifecycleScenarioTest extends TenantTestCase
{
    private function makeAppointment(string $time = '14:00', string $status = Appointment::STATUS_CONFIRMED): Appointment
    {
        $startsAt = now()->addDay()->setTimeFromTimeString($time);

        return Appointment::create([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => $startsAt->toDateString(),
            'time_slot' => $time,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'ends_at_with_buffer' => $startsAt->copy()->addMinutes(30),
            'timezone' => config('app.timezone'),
            'price' => $this->service->price,
            'status' => $status,
            'source' => 'qa-lifecycle',
        ]);
    }

    private function makeQueue(Appointment $appointment, string $status = 'waiting'): Queue
    {
        return Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'LIF' . str_pad((string) $appointment->id, 3, '0', STR_PAD_LEFT),
            'queue_date' => $appointment->starts_at->toDateString(),
            'status' => $status,
            'is_vip' => false,
        ]);
    }

    #[Test]
    public function appointment_status_machine_allows_only_declared_forward_lifecycle_transitions(): void
    {
        $rule = app(AppointmentStatusTransition::class);

        $valid = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['checked_in', 'in_service', 'completed', 'cancelled', 'no_show'],
            'checked_in' => ['in_service', 'completed', 'cancelled', 'no_show'],
            'in_service' => ['completed', 'cancelled'],
        ];

        foreach ($valid as $from => $targets) {
            foreach ($targets as $to) {
                $rule->assertAllowed($from, $to);
            }
        }

        foreach (['completed', 'cancelled', 'no_show'] as $terminal) {
            try {
                $rule->assertAllowed($terminal, 'confirmed');
                $this->fail("Terminal appointment status [{$terminal}] must not transition to confirmed.");
            } catch (DomainException $exception) {
                $this->assertStringContainsString("{$terminal} -> confirmed", $exception->getMessage());
            }
        }
    }

    #[Test]
    public function completed_appointment_moves_its_queue_entry_to_completed(): void
    {
        $appointment = $this->makeAppointment('14:00', Appointment::STATUS_CONFIRMED);
        $this->makeQueue($appointment, 'serving');

        $updated = app(ChangeAppointmentStatus::class)->execute(
            $appointment->id,
            Appointment::STATUS_COMPLETED,
        );

        $this->assertSame(Appointment::STATUS_COMPLETED, $updated->fresh()->status);
        $this->assertSame('completed', $updated->fresh()->queue?->status);
        $this->assertSame($this->customerProfile->id, $updated->customer_id_new);
        $this->assertSame($this->staff->id, $updated->staff_id_new);
    }

    #[Test]
    public function cancelled_appointment_moves_its_queue_entry_to_skipped(): void
    {
        $appointment = $this->makeAppointment('15:00', Appointment::STATUS_CONFIRMED);
        $this->makeQueue($appointment, 'waiting');

        $updated = app(ChangeAppointmentStatus::class)->execute(
            $appointment->id,
            Appointment::STATUS_CANCELLED,
        );

        $this->assertSame(Appointment::STATUS_CANCELLED, $updated->fresh()->status);
        $this->assertSame('skipped', $updated->fresh()->queue?->status);
        $this->assertSame($this->customerProfile->id, $updated->customer_id_new);
        $this->assertSame($this->staff->id, $updated->staff_id_new);
    }
}
