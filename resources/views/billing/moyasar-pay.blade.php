<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الدفع — {{ config('app.name') }}</title>

    {{-- Moyasar Payment Form CSS --}}
    <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/1.14.0/moyasar.css">

    {{-- Tailwind (compiled) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Override Moyasar's LTR layout inside RTL page */
        .mysr-form { direction: ltr; text-align: left; }
        .mysr-form label { font-family: inherit; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-900">

<div class="min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ config('app.name') }}</span>
            </a>
        </div>

        {{-- Plan Summary Card --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden mb-4">
            <div class="bg-gradient-to-l from-indigo-600 to-indigo-500 px-6 py-4">
                <p class="text-indigo-100 text-sm font-medium">الخطة المختارة</p>
                <h2 class="text-white text-xl font-black mt-0.5">{{ $plan->name }}</h2>
            </div>

            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-700">
                <span class="text-slate-600 dark:text-slate-400 text-sm">مبلغ الاشتراك</span>
                <div class="text-right">
                    <span class="text-2xl font-black text-slate-900 dark:text-white">
                        {{ number_format($amount / 100, 2) }}
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm mr-1">ر.س</span>
                </div>
            </div>

            <div class="px-6 py-3 flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 text-xs">دورة الفوترة</span>
                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-full">
                    {{ $plan->billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}
                </span>
            </div>
        </div>

        {{-- Accepted Methods --}}
        <div class="flex items-center justify-center gap-3 mb-5">
            <span class="text-xs text-slate-400">وسائل الدفع المقبولة:</span>
            <div class="flex items-center gap-2">
                {{-- Mada --}}
                <div class="h-6 px-2 bg-white border border-slate-200 rounded flex items-center">
                    <span class="text-[10px] font-black text-indigo-700">مدى</span>
                </div>
                {{-- STC Pay --}}
                <div class="h-6 px-2 bg-[#6D1A7A] rounded flex items-center">
                    <span class="text-[10px] font-black text-white">STC Pay</span>
                </div>
                {{-- Visa --}}
                <div class="h-6 px-2 bg-white border border-slate-200 rounded flex items-center">
                    <span class="text-[10px] font-black text-blue-800 italic">VISA</span>
                </div>
                {{-- Mastercard --}}
                <div class="h-6 px-2 bg-white border border-slate-200 rounded flex items-center gap-0.5">
                    <div class="w-3 h-3 rounded-full bg-red-500 opacity-90"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-400 opacity-90 -mr-1.5"></div>
                </div>
            </div>
        </div>

        {{-- Moyasar Payment Form --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-4 text-center">أدخل بيانات الدفع</h3>
            <div id="moyasar-form"></div>
        </div>

        {{-- Security badges --}}
        <div class="mt-4 flex items-center justify-center gap-4 text-slate-400">
            <div class="flex items-center gap-1 text-xs">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                دفع آمن ومشفر
            </div>
            <div class="flex items-center gap-1 text-xs">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                موثوق بـ Moyasar
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-3">
            بإتمام الدفع، أنت توافق على
            <a href="#" class="text-indigo-500 hover:underline">شروط الخدمة</a>
            و<a href="#" class="text-indigo-500 hover:underline">سياسة الخصوصية</a>
        </p>

    </div>
</div>

{{-- Moyasar JS --}}
<script src="https://cdn.moyasar.com/mpf/1.14.0/moyasar.js"></script>
<script>
    Moyasar.init({
        element:              '#moyasar-form',
        amount:               {{ (int) $amount }},
        currency:             'SAR',
        description:          'اشتراك خطة {{ $plan->name }}',
        publishable_api_key:  '{{ $publishableKey }}',
        callback_url:         '{{ $callbackUrl }}',
        methods:              ['creditcard', 'stcpay'],
        metadata: {
            plan_id:   '{{ $plan->id }}',
            tenant_id: '{{ $tenantId }}',
        },
        on_completed: function(payment) {
            // Payment object is available here if needed
            console.log('Payment completed', payment.id);
        },
        on_failed: function(payment) {
            console.error('Payment failed', payment);
        },
    });
</script>

</body>
</html>
