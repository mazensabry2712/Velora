<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Domain\Booking\Contracts\AppointmentReader;
use App\Domain\Queue\Contracts\QueueReader;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('security')]
final class TenantIsolationResourceScenarioTest extends TenantTestCase
{
    #[Test]
    public function appointment_and_queue_from_tenant_a_are_not_visible_after_switching_to_tenant_b(): void
    {
        $date = today()->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $date,
            'time_slot' => '15:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'QA-' . str_pad((string) $appointment->id, 4, '0', STR_PAD_LEFT),
            'queue_date' => $date,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $tenantAAppointmentId = $appointment->id;
        $tenantAQueueNumber = $queue->queue_number;

        $tenantB = Tenant::create([
            'id' => 'qa-resource-isolation-' . uniqid(),
            'name' => 'QA Resource Isolation B',
        ]);

        try {
            tenancy()->initialize($tenantB);

            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenantB->id],
                '--force' => true,
            ]);

            $appointmentReader = app(AppointmentReader::class);
            $queueReader = app(QueueReader::class);

            $this->assertNull($appointmentReader->find($tenantAAppointmentId));
            $this->assertSame([], $queueReader->status($tenantAQueueNumber, $date)['queue'] ? ['unexpected'] : []);
            $this->assertFalse(
                $queueReader->forDate($date)->contains('queue_number', $tenantAQueueNumber),
            );
        } finally {
            tenancy()->end();
            $tenantB->delete();
        }
    }

    #[Test]
    public function tenant_database_switching_does_not_change_the_business_identity_of_the_current_tenant(): void
    {
        $tenantId = $this->tenant->id;
        $tenantB = Tenant::create([
            'id' => 'qa-resource-context-' . uniqid(),
            'name' => 'QA Resource Context B',
        ]);

        try {
            $this->assertSame($tenantId, tenant()->id);

            tenancy()->initialize($tenantB);
            $this->assertSame($tenantB->id, tenant()->id);

            tenancy()->initialize($this->tenant);
            $this->assertSame($tenantId, tenant()->id);
        } finally {
            tenancy()->end();
            $tenantB->delete();
        }
    }
}
