<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velora</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Arial,sans-serif;background:#f5f7fb;color:#1a202c;direction:rtl;}
        .wrapper{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);}
        .header{background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:36px 40px;text-align:center;}
        .header h1{font-size:22px;color:#fff;font-weight:700;line-height:1.4;}
        .body{padding:36px 40px;}
        .greeting{font-size:15px;color:#374151;margin-bottom:20px;line-height:1.8;}
        .highlight-box{background:#f0fdf4;border-right:4px solid #22c55e;border-radius:8px;padding:16px 20px;margin:20px 0;font-size:14px;line-height:1.7;}
        .highlight-box.warn{background:#fffbeb;border-right-color:#f59e0b;}
        .highlight-box.info{background:#eff6ff;border-right-color:#3b82f6;}
        .highlight-box.danger{background:#fef2f2;border-right-color:#ef4444;}
        .stat-row{display:flex;gap:16px;margin:24px 0;}
        .stat-card{flex:1;background:#f9fafb;border-radius:10px;padding:16px;text-align:center;border:1px solid #e5e7eb;}
        .stat-card .number{font-size:28px;font-weight:800;color:#4f46e5;}
        .stat-card .label{font-size:12px;color:#6b7280;margin-top:4px;}
        .cta-btn{display:block;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff !important;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:700;margin:28px auto;text-align:center;max-width:280px;}
        .step-list{list-style:none;counter-reset:step;}
        .step-list li{counter-increment:step;display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;font-size:14px;color:#374151;}
        .step-list li::before{content:counter(step);background:#4f46e5;color:#fff;width:24px;height:24px;min-width:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin-top:1px;}
        .footer{background:#f9fafb;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;}
        .footer p{font-size:12px;color:#9ca3af;line-height:1.7;}
        .footer a{color:#4f46e5;text-decoration:none;}
        .pricing-table{width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;}
        .pricing-table th{background:#4f46e5;color:#fff;padding:10px 14px;text-align:right;}
        .pricing-table td{padding:10px 14px;border-bottom:1px solid #e5e7eb;}
        .pricing-table tr.pro td{background:#eff6ff;font-weight:700;}
        .badge{display:inline-block;background:#4f46e5;color:#fff;font-size:10px;padding:2px 8px;border-radius:999px;margin-right:6px;}
        .divider{border:none;border-top:1px solid #e5e7eb;margin:24px 0;}
    </style>
</head>
<body>
<div class="wrapper">

    {{-- ========== HEADER ========== --}}
    <div class="header">
        <h1>
            @if     ($nudgeDay === 1) 🎉 مرحباً بك في Velora — ابدأ في 5 دقائق
            @elseif ($nudgeDay === 3) 📲 هل استفدت من Velora حتى الآن؟
            @elseif ($nudgeDay === 7) 📊 تقريرك بعد أسبوع مع Velora
            @else                    ⏰ يومان تقريباً على نهاية تجربتك المجانية
            @endif
        </h1>
    </div>

    {{-- ========== BODY ========== --}}
    <div class="body">

        <p class="greeting">مرحباً <strong>{{ $businessName }}</strong>،</p>

        {{-- ===================== DAY 1 ===================== --}}
        @if ($nudgeDay === 1)

            <p class="greeting">
                حسابك في Velora جاهز تماماً.
                شارك رابط الحجز التالي مع عملائك الآن وابدأ تستقبل المواعيد فوراً.
            </p>

            <div class="highlight-box info">
                <strong>رابط الحجز الخاص بك:</strong><br>
                <a href="{{ $bookingUrl }}" style="color:#4f46e5; word-break:break-all;">{{ $bookingUrl }}</a>
            </div>

            <p style="font-size:14px;color:#374151;font-weight:600;margin-bottom:12px;">ابدأ في 3 خطوات:</p>
            <ul class="step-list">
                <li>أضف خدماتك وأسعارها من <strong>لوحة التحكم → الخدمات</strong></li>
                <li>شارك رابط الحجز على واتساب وانستغرام الآن</li>
                <li>فعّل التذكيرات التلقائية لتقليل حالات الغياب</li>
            </ul>

            <a href="{{ $bookingUrl }}" class="cta-btn">افتح لوحة التحكم ←</a>

        {{-- ===================== DAY 3 ===================== --}}
        @elseif ($nudgeDay === 3)

            <p class="greeting">
                لاحظنا إنك سجّلت قبل 3 أيام.
                @if ($appointmentsCount > 0)
                    وصلتك <strong>{{ $appointmentsCount }} مواعيد</strong> حتى الآن — رائع! 👏
                @else
                    ولم يصلك أي موعد بعد — لا بأس، الأمر يستغرق 5 دقائق فقط.
                @endif
            </p>

            @if ($appointmentsCount === 0)
            <div class="highlight-box warn">
                <strong>💡 نصيحة سريعة:</strong><br>
                أرسل رابط حجزك لأول 5 أشخاص في قائمة اتصالاتك الآن.
                الغالبية يحجزون خلال ساعتين من أول مشاركة.
            </div>
            @else
            <div class="highlight-box">
                <strong>✅ أنت على الطريق الصحيح!</strong><br>
                {{ $appointmentsCount }} موعد تلقائي يوفّر عليك اتصالات يدوية بشكل يومي.
            </div>
            @endif

            <div class="highlight-box info">
                <strong>رابط الحجز:</strong><br>
                <a href="{{ $bookingUrl }}" style="color:#4f46e5;word-break:break-all;">{{ $bookingUrl }}</a>
            </div>

            <a href="{{ $bookingUrl }}" class="cta-btn">انطلق الآن ←</a>

        {{-- ===================== DAY 7 ===================== --}}
        @elseif ($nudgeDay === 7)

            <p class="greeting">
                مضى أسبوع كامل على استخدام Velora.
                إليك ما حققته:
            </p>

            <div class="stat-row">
                <div class="stat-card">
                    <div class="number">{{ $appointmentsCount }}</div>
                    <div class="label">موعد إجمالي</div>
                </div>
                <div class="stat-card">
                    <div class="number">{{ $remindersCount }}</div>
                    <div class="label">تذكير أُرسل</div>
                </div>
                <div class="stat-card">
                    <div class="number">{{ $trialDaysLeft }}</div>
                    <div class="label">يوم متبقٍ من التجربة</div>
                </div>
            </div>

            <hr class="divider">

            <p style="font-size:14px;color:#374151;margin-bottom:12px;">
                لو كل تذكير تلقائي واحد منع غياباً واحداً، وكل موعد قيمته
                <strong>50–100 ريال</strong>، فـ Velora تُعوّض عن نفسها بعد أول أسبوع فقط.
            </p>

            <div class="highlight-box info">
                <strong>استمر بأقل من قهوتين في الشهر:</strong><br>
                اشتراك Starter بـ <strong>99 ريال / شهر</strong> فقط.
                وإذا أردت الوظائف الكاملة — Pro بـ <strong>184 ريال / شهر</strong>.
            </div>

            <table class="pricing-table">
                <thead>
                    <tr>
                        <th>الخطة</th>
                        <th>السعر شهرياً</th>
                        <th>الأبرز</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Starter</td>
                        <td>99 ريال</td>
                        <td>حجز + قائمة انتظار</td>
                    </tr>
                    <tr class="pro">
                        <td>Pro <span class="badge">الأكثر شعبية</span></td>
                        <td>184 ريال</td>
                        <td>كل شيء + تذكيرات + تقارير</td>
                    </tr>
                    <tr>
                        <td>Business</td>
                        <td>299 ريال</td>
                        <td>متقدم + API + أولوية</td>
                    </tr>
                </tbody>
            </table>

            <a href="{{ url('/billing') }}" class="cta-btn">ابدأ الاشتراك الآن ←</a>

        {{-- ===================== DAY 12 ===================== --}}
        @else

            <p class="greeting">
                تجربتك المجانية تنتهي خلال <strong>~{{ $trialDaysLeft }} أيام</strong>.
                لا تدع مواعيدك وعملاءك في الهواء.
            </p>

            <div class="highlight-box danger">
                <strong>⚠️ ماذا سيحدث إن لم تشترك؟</strong><br>
                سيتوقف رابط الحجز عن العمل، وتتوقف التذكيرات التلقائية،
                وسيضطر عملاؤك للعودة إلى الاتصال اليدوي.
            </div>

            <div class="stat-row">
                <div class="stat-card">
                    <div class="number">{{ $appointmentsCount }}</div>
                    <div class="label">موعد سجّلته</div>
                </div>
                <div class="stat-card">
                    <div class="number">{{ $remindersCount }}</div>
                    <div class="label">تذكير أُرسل</div>
                </div>
            </div>

            <div class="highlight-box info">
                <strong>عرض التجديد — قبل انتهاء التجربة:</strong><br>
                اشترك الآن بخطة <strong>Pro بـ 184 ريال</strong> فقط، وتابع من حيث توقفت.
            </div>

            <a href="{{ url('/billing') }}" class="cta-btn">اشترك الآن ← احتفظ ببياناتك</a>

            <p style="font-size:13px;color:#6b7280;text-align:center;margin-top:12px;">
                لديك سؤال؟ رد على هذا الإيميل مباشرةً.
            </p>

        @endif

        <hr class="divider">

        <p style="font-size:12px;color:#9ca3af;text-align:center;">
            لديك {{ $trialDaysLeft }} يوم متبقٍ من تجربتك المجانية.
        </p>

    </div>

    {{-- ========== FOOTER ========== --}}
    <div class="footer">
        <p>
            أنت تتلقى هذا البريد لأنك مسجّل في Velora.<br>
            <a href="{{ url('/admin/settings') }}">إلغاء الاشتراك في النشرات البريدية</a>
            &nbsp;·&nbsp;
            <a href="{{ url('/') }}">velora.app</a>
        </p>
    </div>

</div>
</body>
</html>
