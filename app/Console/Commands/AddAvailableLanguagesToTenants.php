<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Support\Facades\Schema;

class AddAvailableLanguagesToTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:add-languages-column';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add available_languages column to settings table in all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = Tenant::cursor();

        $count = 0;
        foreach ($tenants as $tenant) {
            $count++;
        }

        $this->info("Found {$count} tenant(s)");
        $this->newLine();

        $tenants = Tenant::cursor();

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->id}");

            try {
                // Initialize tenant context
                tenancy()->initialize($tenant);

                // Check if column exists
                if (!Schema::hasColumn('settings', 'available_languages')) {
                    $this->line("  - Column 'available_languages' NOT found. Adding...");

                    Schema::table('settings', function ($table) {
                        $table->json('available_languages')->nullable()->after('language');
                    });

                    $this->line("  - <fg=green>✓</> Column added successfully!");
                } else {
                    $this->line("  - <fg=green>✓</> Column 'available_languages' already exists");
                }

                // End tenant context
                tenancy()->end();

            } catch (\Exception $e) {
                $this->error("  - ✗ Error: " . $e->getMessage());
                tenancy()->end();
            }

            $this->newLine();
        }

        $this->info("Done!");

        return 0;
    }
}
