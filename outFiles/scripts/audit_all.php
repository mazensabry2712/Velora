<?php
/**
 * Velora — Full Translation & View Audit Script
 * Tests every lang file and every blade view for correctness.
 * Run from: php audit_all.php
 */

$base    = __DIR__;
$langDir = $base . '/lang';
$viewDir = $base . '/resources/views/super-admin';

$langs   = ['en','ar','de','es','fr','hi','id','it','ja','ko','nl','pt','ru','tr','zh'];
$results = [];
$passed  = 0;
$failed  = 0;

// ──────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────
function ok(string $msg): void  { global $passed; $passed++; echo "  ✅ $msg\n"; }
function fail(string $msg): void { global $failed; $failed++; echo "  ❌ $msg\n"; }
function info(string $msg): void { echo "  ℹ  $msg\n"; }
function section(string $title): void { echo "\n" . str_repeat('─', 60) . "\n🔷 $title\n" . str_repeat('─', 60) . "\n"; }

// ──────────────────────────────────────────────
// TEST 1: PHP Syntax of all lang files
// ──────────────────────────────────────────────
section("TEST 1 — PHP Syntax of all lang files");

foreach ($langs as $lang) {
    $file = "$langDir/$lang/super-admin.php";
    if (!file_exists($file)) {
        fail("MISSING FILE: lang/$lang/super-admin.php");
        continue;
    }
    $output = shell_exec("php -l \"$file\" 2>&1");
    if (str_contains($output, 'No syntax errors')) {
        ok("lang/$lang/super-admin.php — syntax OK");
    } else {
        fail("lang/$lang/super-admin.php — SYNTAX ERROR: $output");
    }
}

// ──────────────────────────────────────────────
// TEST 2: Key Count per lang file
// ──────────────────────────────────────────────
section("TEST 2 — Key Count Comparison (vs EN)");

$enData    = include "$langDir/en/super-admin.php";
$enCount   = count($enData);
$enKeys    = array_keys($enData);
info("EN file has $enCount keys (reference)");

foreach ($langs as $lang) {
    if ($lang === 'en') continue;
    $file = "$langDir/$lang/super-admin.php";
    if (!file_exists($file)) { fail("Missing: $lang"); continue; }

    $data    = include $file;
    $count   = count($data);
    $missing = array_diff($enKeys, array_keys($data));

    if (count($missing) === 0) {
        ok("$lang — $count keys (complete)");
    } else {
        fail("$lang — $count keys — MISSING " . count($missing) . " keys: " . implode(', ', array_slice($missing, 0, 5)) . (count($missing) > 5 ? '...' : ''));
    }
}

// ──────────────────────────────────────────────
// TEST 3: Dashboard keys have real translations (not EN fallback)
// ──────────────────────────────────────────────
section("TEST 3 — Dashboard Keys: Real Translations (not EN fallback)");

$dashKeys = [
    'dashboard_h1', 'stat_all_companies', 'stat_using_now', 'stat_needs_followup',
    'stat_new_company', 'stat_positive_growth', 'mini_tenants_growth', 'mini_tenants_total',
    'mini_system_status', 'mini_status_active', 'mini_active_subs', 'mini_using_now',
    'mini_services_ok', 'qa_manage_companies', 'qa_manage_sub', 'qa_settings_sub',
    'qa_reports_sub', 'dash_empty_title', 'error_load', 'error_reload',
];

foreach ($langs as $lang) {
    if ($lang === 'en') continue;
    $file = "$langDir/$lang/super-admin.php";
    if (!file_exists($file)) continue;

    $data   = include $file;
    $enData2 = include "$langDir/en/super-admin.php";
    $failedKeys = [];

    foreach ($dashKeys as $key) {
        if (!isset($data[$key])) {
            $failedKeys[] = "$key (MISSING)";
            continue;
        }
        // If value equals EN value → still an EN fallback
        if ($data[$key] === ($enData2[$key] ?? null)) {
            $failedKeys[] = "$key (EN FALLBACK)";
        }
    }

    if (empty($failedKeys)) {
        ok("$lang — all " . count($dashKeys) . " dashboard keys properly translated");
    } else {
        fail("$lang — " . count($failedKeys) . " keys still EN: " . implode(', ', array_slice($failedKeys, 0, 5)) . (count($failedKeys) > 5 ? '...' : ''));
    }
}

// ──────────────────────────────────────────────
// TEST 4: Settings Label/Desc keys exist in all files
// ──────────────────────────────────────────────
section("TEST 4 — Settings Label & Desc Keys (167 keys)");

$settingsKeys = array_filter($enKeys, fn($k) => str_starts_with($k, 'settings_label_') || str_starts_with($k, 'settings_desc_'));
$settingsCount = count($settingsKeys);
info("Found $settingsCount settings_label_*/settings_desc_* keys in EN");

foreach ($langs as $lang) {
    if ($lang === 'en') continue;
    $file = "$langDir/$lang/super-admin.php";
    if (!file_exists($file)) continue;

    $data    = include $file;
    $missing = array_diff($settingsKeys, array_keys($data));

    if (empty($missing)) {
        ok("$lang — all $settingsCount settings keys present");
    } else {
        fail("$lang — missing " . count($missing) . " settings keys: " . implode(', ', array_slice($missing, 0, 3)));
    }
}

// ──────────────────────────────────────────────
// TEST 5: Arabic hardcoded text in Blade views
// ──────────────────────────────────────────────
section("TEST 5 — No Arabic Hardcoded Text in Blade Views");

$views = glob("$viewDir/*.blade.php");
$arabicPattern = '/[\x{0600}-\x{06FF}]{2,}/u'; // 2+ consecutive Arabic chars
$excludePatterns = [
    '/\/\*.*?\*\//s',    // block comments
    '/<!--.*?-->/s',     // HTML comments
    '/\/\/.*?$/m',       // line comments
];

foreach ($views as $viewPath) {
    $viewName = basename($viewPath);
    $content  = file_get_contents($viewPath);

    // Remove comment blocks before checking
    foreach ($excludePatterns as $pat) {
        $content = preg_replace($pat, '', $content);
    }

    // Find Arabic strings NOT inside __() or trans() calls
    preg_match_all($arabicPattern, $content, $matches, PREG_OFFSET_CAPTURE);

    $hardcoded = [];
    foreach ($matches[0] as $match) {
        $arabic  = $match[0];
        $offset  = $match[1];
        $context = substr($content, max(0, $offset - 40), strlen($arabic) + 80);

        // Check if inside a __() translation call
        $isTranslated = preg_match("/__\(['\"].*?" . preg_quote($arabic, '/') . "/u", $context)
                     || preg_match("/trans\(['\"].*?" . preg_quote($arabic, '/') . "/u", $context);

        if (!$isTranslated) {
            $hardcoded[] = "«$arabic» at char $offset";
        }
    }

    if (empty($hardcoded)) {
        ok("$viewName — no hardcoded Arabic text");
    } else {
        fail("$viewName — " . count($hardcoded) . " hardcoded Arabic strings: " . implode(', ', array_slice($hardcoded, 0, 3)));
    }
}

// ──────────────────────────────────────────────
// TEST 6: No direct __() inside @json() in views
// ──────────────────────────────────────────────
section("TEST 6 — No __() Inside @json() Directly");

foreach ($views as $viewPath) {
    $viewName = basename($viewPath);
    $content  = file_get_contents($viewPath);

    // Pattern: @json([ ... __( ... inside same @json call
    preg_match_all('/@json\s*\(([^)]{0,2000})\)/s', $content, $matches);

    $violations = [];
    foreach ($matches[1] as $jsonBody) {
        if (preg_match('/__\s*\(/', $jsonBody)) {
            $violations[] = substr(trim($jsonBody), 0, 60) . '...';
        }
    }

    if (empty($violations)) {
        ok("$viewName — no __() inside @json()");
    } else {
        fail("$viewName — __() found inside @json(): " . array_shift($violations));
    }
}

// ──────────────────────────────────────────────
// TEST 7: No $isAr ternary pattern in views
// ──────────────────────────────────────────────
section("TEST 7 — No \$isAr ? ... : ... Pattern (Language Bias)");

foreach ($views as $viewPath) {
    $viewName = basename($viewPath);
    $content  = file_get_contents($viewPath);

    if (preg_match('/\$isAr\s*\?/', $content)) {
        fail("$viewName — still contains \$isAr ternary (language bias!)");
    } else {
        ok("$viewName — no \$isAr ternary");
    }
}

// ──────────────────────────────────────────────
// TEST 8: All views use __() for displayed strings
// ──────────────────────────────────────────────
section("TEST 8 — Key Translation Strings Present in Views");

$viewKeyMap = [
    'layout.blade.php'             => ['nav_dashboard', 'nav_companies', 'nav_settings'],
    'login.blade.php'              => ['login_page_title', 'login_submit'],
    'dashboard.blade.php'         => ['dashboard_h1', 'stat_all_companies'],
    'tenants.blade.php'           => ['tenants_title', 'tenant_add'],
    'activity-logs.blade.php'     => ['logs_title'],
    'notifications.blade.php'     => ['notif_title'],
    'reports.blade.php'           => ['reports_title'],
    'subscription-plans.blade.php'=> ['plans_title'],
    'settings.blade.php'          => ['settings_label_', 'settings_desc_'],
];

foreach ($viewKeyMap as $viewFile => $keys) {
    $path    = "$viewDir/$viewFile";
    if (!file_exists($path)) { fail("View not found: $viewFile"); continue; }
    $content = file_get_contents($path);

    $missing = [];
    foreach ($keys as $key) {
        if (!str_contains($content, $key)) {
            $missing[] = $key;
        }
    }

    if (empty($missing)) {
        ok("$viewFile — found all expected translation keys");
    } else {
        fail("$viewFile — missing expected keys: " . implode(', ', $missing));
    }
}

// ──────────────────────────────────────────────
// TEST 9: AR file has real Arabic values
// ──────────────────────────────────────────────
section("TEST 9 — AR File Has Real Arabic Values");

$arData     = include "$langDir/ar/super-admin.php";
$arabicCheck = ['dashboard_h1', 'stat_all_companies', 'mini_system_status', 'qa_manage_companies', 'settings_label_app_name'];
$arFailed   = [];

foreach ($arabicCheck as $key) {
    $val = $arData[$key] ?? '';
    if (!preg_match('/[\x{0600}-\x{06FF}]/u', $val)) {
        $arFailed[] = $key . ' = [' . $val . '] (no Arabic chars)';
    }
}

if (empty($arFailed)) {
    ok("AR file — all checked keys contain Arabic characters");
} else {
    foreach ($arFailed as $f) fail("AR: $f");
}

// ──────────────────────────────────────────────
// TEST 10: Sample value spot-checks per language
// ──────────────────────────────────────────────
section("TEST 10 — Spot-Check Translated Values");

$spotChecks = [
    'de'  => ['dashboard_h1'       => 'Haupt-Admin-Dashboard',          'mini_status_active' => 'Aktiv'],
    'fr'  => ['dashboard_h1'       => 'Tableau de bord principal',       'stat_all_companies' => 'Toutes les entreprises'],
    'ko'  => ['dashboard_h1'       => '메인 관리자 대시보드',              'mini_system_status' => '시스템 상태'],
    'ja'  => ['dashboard_h1'       => 'メイン管理ダッシュボード',           'mini_status_active' => 'アクティブ'],
    'zh'  => ['dashboard_h1'       => '主管理员仪表板',                   'stat_all_companies' => '所有注册企业'],
    'ru'  => ['mini_system_status' => 'Статус системы',                  'qa_manage_companies'=> 'Управление компаниями'],
    'ar'  => ['dashboard_h1'       => 'لوحة',                            'stat_all_companies' => 'الشركات'],
    'hi'  => ['dashboard_h1'       => 'मुख्य',                           'mini_status_active' => 'सक्रिय'],
    'tr'  => ['dashboard_h1'       => 'Ana Yönetici Paneli',             'mini_status_active' => 'Aktif'],
    'nl'  => ['dashboard_h1'       => 'Hoofd Admin Dashboard',           'mini_status_active' => 'Actief'],
    'es'  => ['dashboard_h1'       => 'Panel de Administración Principal','mini_status_active' => 'Activo'],
    'it'  => ['dashboard_h1'       => 'Dashboard Amministratore',        'mini_status_active' => 'Attivo'],
    'pt'  => ['dashboard_h1'       => 'Painel Principal',                'mini_status_active' => 'Ativo'],
    'id'  => ['dashboard_h1'       => 'Dashboard Admin Utama',           'mini_status_active' => 'Aktif'],
];

foreach ($spotChecks as $lang => $checks) {
    $file = "$langDir/$lang/super-admin.php";
    if (!file_exists($file)) continue;
    $data = include $file;

    foreach ($checks as $key => $expected) {
        $actual = $data[$key] ?? '(missing)';
        // Use str_contains for partial match (more flexible)
        if (str_contains($actual, $expected)) {
            ok($lang . '.' . $key . ' = [' . $actual . '] ✓');
        } else {
            fail($lang . '.' . $key . ' expected [' . $expected . '] but got [' . $actual . ']');
        }
    }
}

// ──────────────────────────────────────────────
// FINAL REPORT
// ──────────────────────────────────────────────
echo "\n" . str_repeat('═', 60) . "\n";
echo "📊 FINAL REPORT\n";
echo str_repeat('═', 60) . "\n";
echo "  Total Passed : $passed\n";
echo "  Total Failed : $failed\n";
echo "  Total Checks : " . ($passed + $failed) . "\n";
echo str_repeat('─', 60) . "\n";

if ($failed === 0) {
    echo "  🎉 ALL TESTS PASSED — System fully localized & clean!\n";
} else {
    echo "  ⚠️  $failed checks FAILED — see details above.\n";
}

echo str_repeat('═', 60) . "\n";
