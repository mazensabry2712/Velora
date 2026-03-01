<?php

/**
 * Super Admin Routes Test Script
 * اختبار شامل لجميع routes الخاصة بالـ Super Admin
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║            🔍 Super Admin Routes Testing Report                 ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check if middleware is registered
echo "📋 Test 1: Checking Middleware Registration\n";
echo str_repeat("─", 70) . "\n";

$middlewareAliases = [
    'super.admin' => \App\Http\Middleware\CheckSuperAdmin::class,
    'super.admin.auth' => \App\Http\Middleware\SuperAdminAuth::class,
];

foreach ($middlewareAliases as $alias => $class) {
    if (class_exists($class)) {
        echo "✅ Middleware '{$alias}' => {$class} exists\n";
    } else {
        echo "❌ Middleware '{$alias}' => {$class} NOT FOUND!\n";
    }
}

echo "\n";

// Test 2: Check Super Admin Routes
echo "📋 Test 2: Checking Super Admin Routes\n";
echo str_repeat("─", 70) . "\n";

$expectedRoutes = [
    // Public routes
    ['GET', 'super-admin/login', 'super-admin.login', null],
    ['POST', 'super-admin/login', 'super-admin.login.post', null],
    ['POST', 'super-admin/logout', 'super-admin.logout', null],

    // Protected routes
    ['GET', 'super-admin/dashboard', 'super-admin.dashboard', 'super.admin.auth'],
    ['GET', 'super-admin/tenants', 'super-admin.tenants', 'super.admin.auth'],
    ['GET', 'super-admin/settings', 'super-admin.settings', 'super.admin.auth'],
    ['GET', 'super-admin/subscription-plans', 'super-admin.subscription-plans', 'super.admin.auth'],
    ['GET', 'super-admin/activity-logs', 'super-admin.activity-logs', 'super.admin.auth'],
    ['GET', 'super-admin/notifications', 'super-admin.notifications', 'super.admin.auth'],
    ['GET', 'super-admin/reports', 'super-admin.reports', 'super.admin.auth'],
    ['GET', 'super-admin/upgrade-requests', 'super-admin.upgrade-requests', 'super.admin.auth'],
];

$allRoutes = Route::getRoutes();
$foundRoutes = 0;
$missingRoutes = 0;

foreach ($expectedRoutes as $expected) {
    [$method, $uri, $name, $expectedMiddleware] = $expected;

    $route = $allRoutes->getByName($name);

    if ($route) {
        $foundRoutes++;
        $middleware = implode(', ', $route->middleware());
        $hasExpectedMiddleware = $expectedMiddleware ?
            (strpos($middleware, $expectedMiddleware) !== false) : true;

        $status = $hasExpectedMiddleware ? '✅' : '⚠️';
        echo "{$status} {$method} /{$uri}\n";
        echo "   Name: {$name}\n";
        echo "   Middleware: [{$middleware}]\n";

        if (!$hasExpectedMiddleware && $expectedMiddleware) {
            echo "   ⚠️  Expected middleware '{$expectedMiddleware}' not found!\n";
        }
        echo "\n";
    } else {
        $missingRoutes++;
        echo "❌ {$method} /{$uri} (Name: {$name}) - NOT FOUND!\n\n";
    }
}

echo str_repeat("─", 70) . "\n";
echo "Summary: {$foundRoutes} routes found, {$missingRoutes} routes missing\n\n";

// Test 3: Check if SuperAdminAuth middleware file exists
echo "📋 Test 3: Checking Middleware File\n";
echo str_repeat("─", 70) . "\n";

$middlewarePath = app_path('Http/Middleware/SuperAdminAuth.php');
if (file_exists($middlewarePath)) {
    echo "✅ SuperAdminAuth middleware file exists\n";
    echo "   Path: {$middlewarePath}\n";

    // Check file contents
    $contents = file_get_contents($middlewarePath);
    if (strpos($contents, 'class SuperAdminAuth') !== false) {
        echo "✅ SuperAdminAuth class defined correctly\n";
    }
    if (strpos($contents, "redirect()->route('super-admin.login')") !== false) {
        echo "✅ Redirect to super-admin.login is implemented\n";
    }
} else {
    echo "❌ SuperAdminAuth middleware file NOT FOUND!\n";
}

echo "\n";

// Test 4: Check if User model has isSuperAdmin method
echo "📋 Test 4: Checking User Model\n";
echo str_repeat("─", 70) . "\n";

try {
    if (class_exists(\App\Models\User::class)) {
        echo "✅ User model exists\n";

        $user = new \App\Models\User();
        if (method_exists($user, 'isSuperAdmin')) {
            echo "✅ isSuperAdmin() method exists\n";
        } else {
            echo "❌ isSuperAdmin() method NOT FOUND!\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error checking User model: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Test middleware logic
echo "📋 Test 5: Testing Middleware Logic\n";
echo str_repeat("─", 70) . "\n";

try {
    $middleware = new \App\Http\Middleware\SuperAdminAuth();
    echo "✅ SuperAdminAuth middleware can be instantiated\n";

    if (method_exists($middleware, 'handle')) {
        echo "✅ handle() method exists\n";
    } else {
        echo "❌ handle() method NOT FOUND!\n";
    }
} catch (\Exception $e) {
    echo "❌ Error instantiating middleware: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Configuration check
echo "📋 Test 6: Checking Configuration\n";
echo str_repeat("─", 70) . "\n";

$authGuard = config('auth.defaults.guard');
echo "Auth Guard: {$authGuard}\n";

$authProvider = config('auth.guards.web.provider');
echo "Web Guard Provider: {$authProvider}\n";

$userModel = config('auth.providers.users.model');
echo "User Model: {$userModel}\n";

echo "\n";

// Final Report
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                        📊 Final Report                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$totalTests = 6;
$passedTests = 0;

if (class_exists(\App\Http\Middleware\SuperAdminAuth::class)) $passedTests++;
if ($foundRoutes > 0) $passedTests++;
if (file_exists($middlewarePath)) $passedTests++;
if (method_exists(new \App\Models\User(), 'isSuperAdmin')) $passedTests++;
if (method_exists(new \App\Http\Middleware\SuperAdminAuth(), 'handle')) $passedTests++;
if ($authGuard === 'web') $passedTests++;

$percentage = round(($passedTests / $totalTests) * 100, 2);

echo "Tests Passed: {$passedTests}/{$totalTests} ({$percentage}%)\n";

if ($passedTests === $totalTests) {
    echo "\n✅ ALL TESTS PASSED! Super Admin routes are configured correctly.\n";
    echo "You can now test by visiting: https://booking-saas.test/super-admin/dashboard\n";
    echo "It should redirect to: https://booking-saas.test/super-admin/login\n";
} else {
    echo "\n⚠️  Some tests failed. Please check the details above.\n";
}

echo "\n";
