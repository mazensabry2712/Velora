<?php

/**
 * Link all existing tenants to Herd
 * Run this script once to create herd links for all existing tenants
 *
 * Usage: php link-existing-tenants.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;

echo "🔗 Linking existing tenants to Herd...\n\n";

$tenants = Tenant::with('domains')->get();

if ($tenants->isEmpty()) {
    echo "⚠️  No tenants found.\n";
    exit(0);
}

$projectPath = base_path();
$linked = 0;
$skipped = 0;
$failed = 0;

foreach ($tenants as $tenant) {
    $domain = $tenant->domains()->first();

    if (!$domain) {
        echo "⚠️  Tenant {$tenant->id} has no domain. Skipping...\n";
        $skipped++;
        continue;
    }

    $domainName = $domain->domain;

    // Only process .test domains
    if (!str_ends_with($domainName, '.test')) {
        echo "⏭️  Skipping non-.test domain: {$domainName}\n";
        $skipped++;
        continue;
    }

    // Extract link name (remove .test)
    $linkName = str_replace('.test', '', $domainName);

    try {
        // Check if link already exists
        $configPath = getenv('HOME') ?: getenv('USERPROFILE');
        $linkPath = $configPath . '\\.config\\herd\\config\\valet\\Sites\\' . $linkName;

        if (is_link($linkPath)) {
            echo "✅ Link already exists: {$linkName}\n";
            $linked++;
            continue;
        }

        // Create herd link
        $command = "cd \"{$projectPath}\" && herd link {$linkName} 2>&1";
        $output = shell_exec($command);

        echo "✅ Linked: {$domainName} → {$linkName}\n";
        if ($output) {
            echo "   Output: " . trim($output) . "\n";
        }

        $linked++;

    } catch (\Exception $e) {
        echo "❌ Failed to link {$domainName}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat('─', 50) . "\n";
echo "📊 Summary:\n";
echo "✅ Linked: {$linked}\n";
echo "⏭️  Skipped: {$skipped}\n";
echo "❌ Failed: {$failed}\n";
echo "\n🎉 Done! All .test domains should now be accessible.\n";
