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
            $this->expectException(DomainException::class);

            try {
                $rule->assertAllowed($terminal, 'confirmed');
            } catch (DomainException $exception) {
                // Keep testing each terminal state independently.
                continue;
            }
        }
    }

    #[Test]
    public function completed_appointment_moves_its_queue_entry_to_completed(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '14:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'LIF001',
            'queue_date' => today()->toDateString(),
            'status' => 'serving',
            'is_vip' => false,
        ]);

        $updated = app(ChangeAppointmentStatus::class)->execute(
            $appointment->id,
            Appointment::STATUS_COMPLETED,
        );

        $this->assertSame(Appointment::STATUS_COMPLETED, $updated->status);
        $this->assertSame('completed', $updated->queue?->fresh()->status);
    }

    #[Test]
    public function cancelled_appointment_moves_its_queue_entry_to_skipped(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '15:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'LIF002',
            'queue_date' => today()->toDateString(),
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $updated = app(ChangeAppointmentStatus::class)->execute(
            $appointment->id,
            Appointment::STATUS_CANCELLED,
        );

        $this->assertSame(Appointment::STATUS_CANCELLED, $updated->status);
        $this->assertSame('skipped', $updated->queue?->fresh()->status);
    }
}
