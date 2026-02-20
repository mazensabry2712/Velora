<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اختبار الإحصائيات</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .value { font-size: 24px; color: #2563eb; }
    </style>
</head>
<body>
    <h1>✅ اختبار الإحصائيات المحسّنة</h1>

    <div class="card">
        <h2>البيانات من الداتابيز:</h2>
        <?php
        require __DIR__ . '/../vendor/autoload.php';
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        try {
            $totalAppointments = \App\Models\Appointment::count();
            $pastAppointments = \App\Models\Appointment::where('date', '<', today())->count();
            $completedPast = \App\Models\Appointment::where('date', '<', today())->where('status', 'completed')->count();
            $thisMonthCount = \App\Models\Appointment::whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
            $daysInMonth = now()->day;

            // Calculations
            $noShowRate = $pastAppointments > 0 ? round((($pastAppointments - $completedPast) / $pastAppointments) * 100, 1) : 0;
            $avgDaily = $daysInMonth > 0 ? round($thisMonthCount / $daysInMonth, 1) : 0;

            $topServices = \App\Models\Appointment::select('service_type', \DB::raw('count(*) as total'))
                ->whereNotNull('service_type')
                ->groupBy('service_type')
                ->orderBy('total', 'desc')
                ->limit(3)
                ->get();

            echo "<p class='success'>✅ الاتصال بالداتابيز نجح!</p>";
            echo "<p><strong>إجمالي المواعيد:</strong> <span class='value'>{$totalAppointments}</span></p>";
            echo "<p><strong>المواعيد السابقة:</strong> <span class='value'>{$pastAppointments}</span></p>";
            echo "<p><strong>المكتمل منها:</strong> <span class='value'>{$completedPast}</span></p>";
            echo "<p><strong>مواعيد هذا الشهر:</strong> <span class='value'>{$thisMonthCount}</span></p>";
            echo "<p><strong>الأيام المارة:</strong> <span class='value'>{$daysInMonth}</span></p>";
            echo "<hr>";
            echo "<h3>الإحصائيات المحسوبة:</h3>";
            echo "<p><strong>معدل عدم الحضور:</strong> <span class='value'>{$noShowRate}%</span></p>";
            echo "<p><strong>متوسط يومي:</strong> <span class='value'>{$avgDaily}</span></p>";
            echo "<p><strong>أكثر الخدمات طلباً:</strong></p>";
            if ($topServices->count() > 0) {
                echo "<ul>";
                foreach ($topServices as $service) {
                    echo "<li><strong>{$service->service_type}:</strong> {$service->total} موعد</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='error'>❌ لا توجد بيانات خدمات!</p>";
            }

        } catch (\Exception $e) {
            echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="card">
        <h3>📝 الخطوات التالية:</h3>
        <ol>
            <li>لو الأرقام هنا <strong class='success'>صح</strong>، يبقى المشكلة في الـ browser cache</li>
            <li>اعمل <strong>Hard Refresh</strong> للصفحة: <code>Ctrl + Shift + R</code> أو <code>Ctrl + F5</code></li>
            <li>لو لسه مش شغالة، امسح الـ browser cache تماماً</li>
            <li>جرّب في <strong>Private/Incognito Window</strong></li>
        </ol>
    </div>
</body>
</html>
