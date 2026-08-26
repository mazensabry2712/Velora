<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'resources/views/admin/appointments/index.blade.php',
    'app/Http/Controllers/SuperAdmin/TenantController.php',
    'tests/Feature/PublicBookingTest.php',
    'routes/api.php',
];

foreach ($files as $file) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    if (!is_file($path)) {
        fwrite(STDERR, "Missing file: {$file}\n");
        exit(1);
    }
}

// 1) Balance the appointments page's @endpush with a scripts push.
$path = $root . '/resources/views/admin/appointments/index.blade.php';
$content = file_get_contents($path);
$needle = "    <script>\n        console.log('Appointments script starting...');";
$replacement = "@push('scripts')\n{$needle}";
if (str_contains($content, $needle) && !str_contains($content, "@push('scripts')\n{$needle}")) {
    $content = str_replace($needle, $replacement, $content, $count);
    if ($count !== 1) {
        throw new RuntimeException("Unexpected appointments script marker count: {$count}");
    }
    file_put_contents($path, $content);
    echo "Fixed appointments Blade script stack.\n";
}

// 2) Do not eager-load tenant users from the central Tenant model.
$path = $root . '/app/Http/Controllers/SuperAdmin/TenantController.php';
$content = file_get_contents($path);
$old = "Tenant::with(['domains', 'settings', 'users'])->findOrFail($id)";
$new = "Tenant::with(['domains', 'settings'])->findOrFail($id)";
if (str_contains($content, $old)) {
    $content = str_replace($old, $new, $content, $count);
    if ($count !== 1) {
        throw new RuntimeException("Unexpected TenantController relation marker count: {$count}");
    }
    file_put_contents($path, $content);
    echo "Removed invalid Tenant::users eager-load.\n";
}

// 3) Make the working-days test deterministic regardless of tenant fixtures/seeders.
$path = $root . '/tests/Feature/PublicBookingTest.php';
$content = file_get_contents($path);
$old = "    public function workingdays_api_returns_active_days(): void\n    {\n        WorkingDay::create([";
$new = "    public function workingdays_api_returns_active_days(): void\n    {\n        WorkingDay::query()->delete();\n\n        WorkingDay::create([";
if (str_contains($content, $old) && !str_contains($content, "workingdays_api_returns_active_days(): void\n    {\n        WorkingDay::query()->delete();")) {
    $content = str_replace($old, $new, $content, $count);
    if ($count !== 1) {
        throw new RuntimeException("Unexpected working-days test marker count: {$count}");
    }
    file_put_contents($path, $content);
    echo "Made working-days test deterministic.\n";
}

// 4) Use Spatie's role scope instead of the legacy role relationship query.
$path = $root . '/routes/api.php';
$content = file_get_contents($path);
$old = "return \\App\\Models\\User::whereHas('role', function($query) {\n            $query->where('name', 'Staff');\n        })->select('id', 'name')->get();";
$new = "return \\App\\Models\\User::role('Staff')->select('id', 'name')->get();";
if (str_contains($content, $old)) {
    $content = str_replace($old, $new, $content, $count);
    if ($count !== 1) {
        throw new RuntimeException("Unexpected staff role query marker count: {$count}");
    }
    file_put_contents($path, $content);
    echo "Switched public staff lookup to Spatie role scope.\n";
}

echo "Repair patch completed. Run: php artisan optimize:clear && php artisan test --compact\n";
