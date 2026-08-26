<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('security')]
#[Group('tenancy')]
class TenantIsolationTest extends TenantTestCase
{
    #[Test]
    public function tenant_databases_are_isolated(): void
    {
        $firstTenantId = $this->tenant->id;
        $secondTenantId = 'isolation-' . Str::lower(Str::random(12));
        $secondDatabasePath = database_path(
            config('tenancy.database.prefix', 'tenant') . $secondTenantId
        );

        tenancy()->end();

        try {
            $secondTenant = Tenant::create([
                'id' => $secondTenantId,
                'name' => 'Second Test Clinic',
            ]);

            tenancy()->initialize($secondTenant);

            Artisan::call('tenants:migrate', [
                '--tenants' => [$secondTenant->id],
                '--force' => true,
            ]);

            $foreignUser = User::create([
                'name' => 'Foreign Tenant User',
                'email' => 'foreign-' . Str::lower(Str::random(8)) . '@test.com',
                'password' => 'password',
                'role_id' => $this->adminRole->id,
            ]);

            $foreignAppointment = Appointment::create([
                'customer_id' => $foreignUser->id,
                'staff_id' => $foreignUser->id,
                'service_id' => $this->service->id,
                'date' => today()->addDay()->format('Y-m-d'),
                'time_slot' => '15:00',
                'status' => 'pending',
            ]);

            $foreignEmail = $foreignUser->email;
            $foreignAppointmentId = $foreignAppointment->id;

            tenancy()->end();
            $firstTenant = Tenant::findOrFail($firstTenantId);
            tenancy()->initialize($firstTenant);

            $this->assertDatabaseMissing('users', ['email' => $foreignEmail]);
            $this->assertDatabaseMissing('appointments', ['id' => $foreignAppointmentId]);
            $this->assertNull(User::where('email', $foreignEmail)->first());
        } finally {
            tenancy()->end();

            if (file_exists($secondDatabasePath)) {
                @unlink($secondDatabasePath);
            }

            if (Tenant::whereKey($secondTenantId)->exists()) {
                Tenant::whereKey($secondTenantId)->delete();
            }

            $firstTenant = Tenant::find($firstTenantId);
            if ($firstTenant) {
                tenancy()->initialize($firstTenant);
            }
        }
    }
}
