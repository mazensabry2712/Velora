<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
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
                'password' => Hash::make('password'),
            ]);

            $foreignCustomer = Customer::create([
                'user_id' => $foreignUser->id,
                'first_name' => 'Foreign',
                'last_name' => 'Customer',
                'email' => $foreignUser->email,
                'phone' => '+201000000099',
                'is_blocked' => false,
                'ltv_tier' => 'new',
            ]);

            $foreignStaff = Staff::create([
                'user_id' => $foreignUser->id,
                'first_name' => 'Foreign',
                'last_name' => 'Staff',
                'email' => $foreignUser->email,
            ]);

            $secondService = Service::create([
                'name' => 'Second Tenant Service',
                'duration' => 30,
                'price' => 100,
                'is_active' => true,
            ]);

            $foreignAppointment = Appointment::create([
                'customer_id_new' => $foreignCustomer->id,
                'staff_id_new' => $foreignStaff->id,
                'service_id' => $secondService->id,
                'date' => today()->addDay()->format('Y-m-d'),
                'time_slot' => '15:00',
                'starts_at' => today()->addDay()->setTime(15, 0),
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

            $firstTenant = Tenant::find($firstTenantId);
            if ($firstTenant) {
                tenancy()->initialize($firstTenant);
            }
        }
    }
}
