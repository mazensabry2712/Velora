<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Application\Booking\Actions\ChangeAppointmentStatus;
use App\Application\Booking\Actions\UpdateAdminAppointment;
use App\Domain\Booking\Rules\AppointmentStatusTransition;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\Invoice;
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
    public function completed_appointment_moves_its_queue_entry_to_completed_and_reconciles_its_invoice(): void
    {
        $appointment = $this->makeAppointment('14:00', Appointment::STATUS_CONFIRMED);
        $this->makeQueue($appointment, 'serving');
        $invoiceCountBefore = Invoice::where('appointment_id', $appointment->id)->count();

        $updated = app(ChangeAppointmentStatus::class)->execute(
            $appointment->id,
            Appointment::STATUS_COMPLETED,
        );

        $fresh = $updated->fresh(['queue']);
        $invoice = Invoice::where('appointment_id', $appointment->id)->latest('id')->first();

        $this->assertSame(Appointment::STATUS_COMPLETED, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertSame('completed', $fresh->queue?->status);
        $this->assertSame($this->customerProfile->id, $fresh->customer_id_new);
        $this->assertSame($this->staff->id, $fresh->staff_id_new);
        $this->assertSame($invoiceCountBefore + 1, Invoice::where('appointment_id', $appointment->id)->count());
        $this->assertNotNull($invoice);
        $this->assertSame($this->customerProfile->id, $invoice?->customer_id);
        $this->assertSame($appointment->id, $invoice?->appointment_id);
        $this->assertSame((string) $this->service->price, (string) $invoice?->amount);
        $this->assertSame('pending', $invoice?->status);
    }

    #[Test]
    public function cancelled_appointment_moves_its_queue_entry_to_skipped_and_records_cancelled_at(): void
    {
        $appointment = $this->makeAppointment('15:00', Appointment::STATUS_CONFIRMED);
        $this->makeQueue($appointment, 'waiting');

        $updated = app(ChangeAppointmentStatus::class)->execute(
            $appointment->id,
            Appointment::STATUS_CANCELLED,
        );

        $fresh = $updated->fresh(['queue']);

        $this->assertSame(Appointment::STATUS_CANCELLED, $fresh->status);
        $this->assertNotNull($fresh->cancelled_at);
        $this->assertSame('skipped', $fresh->queue?->status);
        $this->assertSame($this->customerProfile->id, $fresh->customer_id_new);
        $this->assertSame($this->staff->id, $fresh->staff_id_new);
    }

    #[Test]
    public function no_show_moves_its_queue_entry_to_skipped_and_records_no_show_at(): void
    {
        $appointment = $this->makeAppointment('16:00', Appointment::STATUS_CONFIRMED);
        $this->makeQueue($appointment, 'serving');

        $updated = app(ChangeAppointmentStatus::class)->execute(
            $appointment->id,
            Appointment::STATUS_NO_SHOW,
        );

        $fresh = $updated->fresh(['queue']);

        $this->assertSame(Appointment::STATUS_NO_SHOW, $fresh->status);
        $this->assertNotNull($fresh->no_show_at);
        $this->assertSame('skipped', $fresh->queue?->status);
    }

    #[Test]
    public function rescheduling_updates_the_canonical_schedule_and_reconciles_the_queue_date(): void
    {
        $appointment = $this->makeAppointment('18:00', Appointment::STATUS_CONFIRMED);
        $queue = $this->makeQueue($appointment, 'waiting');
        $newStartsAt = now()->addDays(3)->setTime(11, 30);

        $updated = app(UpdateAdminAppointment::class)->execute($appointment->id, [
            'customer_name' => $this->customerProfile->full_name,
            'customer_phone' => $this->customerProfile->phone,
            'customer_email' => $this->customerProfile->email,
            'appointment_date' => $newStartsAt->toDateString(),
            'appointment_time' => $newStartsAt->format('H:i'),
            'staff_id' => $appointment->staff_id_new,
            'service_id' => $appointment->service_id,
            'status' => Appointment::STATUS_CONFIRMED,
            'service_type' => $appointment->service_type,
            'notes' => $appointment->notes,
        ]);

        $fresh = $updated->fresh(['queue']);

        $this->assertTrue($fresh->starts_at?->equalTo($newStartsAt));
        $this->assertSame($newStartsAt->toDateString(), $fresh->date->toDateString());
        $this->assertSame($newStartsAt->format('H:i'), $fresh->time_slot);
        $this->assertSame($newStartsAt->toDateString(), $queue->fresh()->queue_date?->toDateString());
        $this->assertSame(Appointment::STATUS_CONFIRMED, $fresh->status);
    }

    #[Test]
    public function each_status_change_creates_a_complete_history_record(): void
    {
        $appointment = $this->makeAppointment('17:00', Appointment::STATUS_PENDING);

        app(ChangeAppointmentStatus::class)->execute($appointment->id, Appointment::STATUS_CONFIRMED);
        app(ChangeAppointmentStatus::class)->execute($appointment->id, Appointment::STATUS_CANCELLED);

        $history = AppointmentStatusHistory::query()
            ->where('appointment_id', $appointment->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $history);
        $this->assertSame('pending', $history[0]->from_status);
        $this->assertSame('confirmed', $history[0]->to_status);
        $this->assertSame('confirmed', $history[1]->from_status);
        $this->assertSame('cancelled', $history[1]->to_status);
        $this->assertNotNull($history[0]->created_at);
        $this->assertNotNull($history[1]->created_at);
    }
}
