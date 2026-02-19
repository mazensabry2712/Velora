<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\Domain;

class LinkTenantDomain
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Domain $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    public function handle(): void
    {
        $domainName = $this->domain->domain;
        $tenant = $this->domain->tenant;

        // Only process .test domains (local development)
        if (!str_ends_with($domainName, '.test')) {
            Log::info("Skipping non-.test domain: {$domainName}");
            return;
        }

        // Extract subdomain for herd link (remove .test)
        // e.g., "mazen.booking-saas.test" -> "mazen.booking-saas"
        $linkName = str_replace('.test', '', $domainName);

        try {
            // Get the project root path
            $projectPath = base_path();

            // Execute herd link command
            $command = "cd \"{$projectPath}\" && herd link {$linkName}";

            // Use shell_exec for Windows compatibility
            $output = shell_exec($command . ' 2>&1');

            Log::info("Herd link created for domain", [
                'tenant_id' => $tenant->id ?? 'unknown',
                'domain' => $domainName,
                'link_name' => $linkName,
                'output' => $output
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create herd link for domain", [
                'tenant_id' => $tenant->id ?? 'unknown',
                'domain' => $domainName,
                'error' => $e->getMessage()
            ]);
        }
    }
}
