<?php

/**
 * Dashboard APIs Test Script
 * اختبار شامل لجميع APIs الخاصة بالـ Dashboard
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║         🧪 Dashboard Components Testing Report                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Check Routes Existence
echo "📋 Test 1: Checking Dashboard Routes\n";
echo str_repeat("─", 70) . "\n";

$requiredRoutes = [
    'super-admin/dashboard',
    'super-admin/dashboard/tenants-overview',
    'super-admin/dashboard/system-stats',
    'super-admin/dashboard/subscription-stats',
    'super-admin/dashboard/activity-summary',
    'super-admin/dashboard/growth-metrics',
];

foreach ($requiredRoutes as $routeName) {
    $allRoutes = Route::getRoutes();
    $found = false;

    foreach ($allRoutes as $route) {
        if (strpos($route->uri(), $routeName) !== false) {
            $found = true;
            break;
        }
    }

    if ($found) {
        echo "✅ Route: {$routeName}\n";
        $passed++;
    } else {
        echo "❌ Route: {$routeName} - NOT FOUND!\n";
        $failed++;
    }
}

echo "\n";

// Test 2: Check Controller Methods
echo "📋 Test 2: Checking Controller Methods\n";
echo str_repeat("─", 70) . "\n";

$controller = new \App\Http\Controllers\SuperAdmin\DashboardController();
$methods = ['index', 'tenantsOverview', 'systemStats', 'subscriptionStats', 'activitySummary', 'growthMetrics'];

foreach ($methods as $method) {
    if (method_exists($controller, $method)) {
        echo "✅ Method: {$method}()\n";
        $passed++;
    } else {
        echo "❌ Method: {$method}() - NOT FOUND!\n";
        $failed++;
    }
}

echo "\n";

// Test 3: Check Database Tables
echo "📋 Test 3: Checking Database Tables\n";
echo str_repeat("─", 70) . "\n";

$requiredTables = ['tenants', 'domains', 'subscription_plans', 'tenant_subscriptions', 'activity_logs'];

foreach ($requiredTables as $table) {
    try {
        DB::table($table)->limit(1)->count();
        echo "✅ Table: {$table}\n";
        $passed++;
    } catch (\Exception $e) {
        echo "❌ Table: {$table} - NOT FOUND!\n";
        $failed++;
    }
}

echo "\n";

// Test 4: Check Models
echo "📋 Test 4: Checking Models\n";
echo str_repeat("─", 70) . "\n";

$models = [
    'Tenant' => \App\Models\Tenant::class,
    'SubscriptionPlan' => \App\Models\SubscriptionPlan::class,
];

foreach ($models as $name => $class) {
    if (class_exists($class)) {
        echo "✅ Model: {$name}\n";
        $passed++;
    } else {
        echo "❌ Model: {$name} - NOT FOUND!\n";
        $failed++;
    }
}

echo "\n";

// Test 5: Test Data Retrieval
echo "📋 Test 5: Testing Data Retrieval\n";
echo str_repeat("─", 70) . "\n";

try {
    $tenants = Tenant::all();
    $activeTenants = $tenants->filter(fn($t) => $t->active);
    $inactiveTenants = $tenants->filter(fn($t) => !$t->active);
    $thisMonth = Tenant::whereMonth('created_at', now()->month)->count();

    echo "✅ Total Tenants: " . $tenants->count() . "\n";
    echo "✅ Active Tenants: " . $activeTenants->count() . "\n";
    echo "✅ Inactive Tenants: " . $inactiveTenants->count() . "\n";
    echo "✅ This Month: " . $thisMonth . "\n";
    $passed += 4;
} catch (\Exception $e) {
    echo "❌ Data Retrieval Failed: " . $e->getMessage() . "\n";
    $failed += 4;
}

echo "\n";

// Test 6: Test API Response Format
echo "📋 Test 6: Testing API Response Format\n";
echo str_repeat("─", 70) . "\n";

try {
    $controller = new \App\Http\Controllers\SuperAdmin\DashboardController();
    $response = $controller->index();
    $data = json_decode($response->getContent(), true);

    $requiredFields = ['success', 'data'];
    $requiredDataFields = ['total_tenants', 'active_tenants', 'inactive_tenants', 'tenants_this_month', 'recent_tenants'];

    foreach ($requiredFields as $field) {
        if (isset($data[$field])) {
            echo "✅ Response Field: {$field}\n";
            $passed++;
        } else {
            echo "❌ Response Field: {$field} - MISSING!\n";
            $failed++;
        }
    }

    foreach ($requiredDataFields as $field) {
        if (isset($data['data'][$field])) {
            echo "✅ Data Field: {$field}\n";
            $passed++;
        } else {
            echo "❌ Data Field: {$field} - MISSING!\n";
            $failed++;
        }
    }
} catch (\Exception $e) {
    echo "❌ API Response Test Failed: " . $e->getMessage() . "\n";
    $failed += 7;
}

echo "\n";

// Test 7: Test JavaScript Functions
echo "📋 Test 7: Checking JavaScript Functions in View\n";
echo str_repeat("─", 70) . "\n";

$viewPath = resource_path('views/super-admin/dashboard.blade.php');
if (file_exists($viewPath)) {
    $viewContent = file_get_contents($viewPath);
    $requiredFunctions = ['loadDashboard', 'loadSubscriptionStats', 'loadActivitySummary', 'formatDate', 'formatCurrency'];

    foreach ($requiredFunctions as $func) {
        if (strpos($viewContent, $func) !== false) {
            echo "✅ Function: {$func}()\n";
            $passed++;
        } else {
            echo "❌ Function: {$func}() - NOT FOUND!\n";
            $failed++;
        }
    }
} else {
    echo "❌ View file not found!\n";
    $failed += 5;
}

echo "\n";

// Test 8: Test Subscription Stats Method
echo "📋 Test 8: Testing Subscription Stats\n";
echo str_repeat("─", 70) . "\n";

try {
    $controller = new \App\Http\Controllers\SuperAdmin\DashboardController();

    if (method_exists($controller, 'subscriptionStats')) {
        $response = $controller->subscriptionStats();
        $data = json_decode($response->getContent(), true);

        if ($data['success']) {
            $expectedFields = ['total_revenue', 'monthly_revenue', 'total_subscriptions', 'active_subscriptions'];
            $allFieldsExist = true;

            foreach ($expectedFields as $field) {
                if (!isset($data['data'][$field])) {
                    $allFieldsExist = false;
                    echo "❌ Field Missing: {$field}\n";
                    $failed++;
                } else {
                    echo "✅ Field: {$field} = " . $data['data'][$field] . "\n";
                    $passed++;
                }
            }
        } else {
            echo "❌ API returned success=false\n";
            $failed++;
        }
    } else {
        echo "❌ subscriptionStats method not found\n";
        $failed++;
    }
} catch (\Exception $e) {
    echo "❌ Subscription Stats Test Failed: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Final Report
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                        📊 Final Report                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

echo "Tests Passed: " . $passed . "\n";
echo "Tests Failed: " . $failed . "\n";
echo "Total Tests: " . $total . "\n";
echo "Success Rate: " . $percentage . "%\n\n";

if ($percentage == 100) {
    echo "✅ ALL TESTS PASSED! Dashboard is fully functional.\n";
} else if ($percentage >= 80) {
    echo "⚠️  Most tests passed. Some minor issues detected.\n";
} else if ($percentage >= 50) {
    echo "⚠️  Several tests failed. Review the errors above.\n";
} else {
    echo "❌ Critical issues detected. Dashboard may not work properly.\n";
}

echo "\n";
echo "🔗 Test the dashboard at: https://booking-saas.test/super-admin/dashboard\n";
echo "🔗 Test APIs at: https://booking-saas.test/api/super-admin/dashboard\n";
echo "\n";
