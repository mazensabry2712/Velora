<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Demo Clinic',
            'active' => true,
        ]);

        $tenant->domains()->create([
            'domain' => 'demo.localhost',
        ]);

        $tenant->domains()->create([
            'domain' => 'demo.velora.test',
        ]);

        $this->command->info('Demo tenant created: demo.velora.test');
        $this->command->info('Tenant ID: ' . $tenant->id);
        $this->command->info('Demo users were not seeded because tenant database is not initialized yet.');
    }
}
