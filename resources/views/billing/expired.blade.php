<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Upgrade Your Plan — Velora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    brand: { 400:'#8b76ff', 500:'#6C63FF', 600:'#5b4ff7' },
                    surface: '#0f0e1a',
                },
                fontFamily: { sans: ['Inter', 'sans-serif'] },
            }}
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-text { background: linear-gradient(135deg,#6C63FF,#a78bfa,#38bdf8); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .glass { background:rgba(255,255,255,0.05); backdrop-filter:blur(16px); border:1px solid rgba(255,255,255,0.1); }
        .btn-primary { background:linear-gradient(135deg,#6C63FF,#8b76ff); box-shadow:0 8px 30px rgba(108,99,255,0.4); transition:all 0.2s; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 12px 40px rgba(108,99,255,0.55); }
    </style>
</head>
<body class="bg-surface text-white min-h-screen antialiased">

    {{-- Main Layout --}}
    <div class="min-h-screen flex flex-col">

        {{-- Header --}}
        <header class="border-b border-white/5 px-6 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg btn-primary flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="font-bold text-lg">Velora</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="https://wa.me/1234567890?text=I+need+help+with+my+Velora+subscription"
                   target="_blank"
                   class="flex items-center gap-2 text-sm text-green-400 hover:text-green-300 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/>
                    </svg>
                    WhatsApp Support
                </a>
                <a href="mailto:support@velora.com" class="text-sm text-gray-400 hover:text-white transition-colors">
                    Email Support
                </a>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="flex-1 max-w-5xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-12">

            {{-- Status Banner --}}
            @if($subscription)
            <div class="mb-8 p-4 rounded-xl
                {{ $subscription->status === 'grace' ? 'bg-orange-500/10 border border-orange-500/30' : 'bg-red-500/10 border border-red-500/30' }}">
                <div class="flex items-start gap-3">
                    <div class="text-2xl">{{ $subscription->status === 'grace' ? '⚠️' : '🚨' }}</div>
                    <div>
                        @if($subscription->status === 'trial')
                        <h2 class="font-bold text-yellow-300 mb-1">Your trial has ended</h2>
                        <p class="text-sm text-gray-300">Your 14-day free trial has expired. Upgrade now to continue using Velora.</p>
                        @elseif($subscription->status === 'grace')
                        <h2 class="font-bold text-orange-300 mb-1">Grace Period Active</h2>
                        <p class="text-sm text-gray-300">
                            You have
                            <strong class="text-orange-300">
                                {{ $subscription->grace_ends_at ? max(0, (int) now()->diffInDays($subscription->grace_ends_at, false)) : 0 }} day(s)
                            </strong>
                            remaining. New appointments and data creation are paused. Your existing data is safe.
                        </p>
                        @elseif($subscription->status === 'expired')
                        <h2 class="font-bold text-red-300 mb-1">Subscription Expired</h2>
                        <p class="text-sm text-gray-300">Your subscription has expired. Upgrade now to restore full access. Your data is preserved for 30 days.</p>
                        @elseif($subscription->status === 'cancelled')
                        <h2 class="font-bold text-red-300 mb-1">Subscription Cancelled</h2>
                        <p class="text-sm text-gray-300">Your subscription was cancelled. Reactivate now to continue using your account.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Page Title --}}
            <div class="text-center mb-12">
                <h1 class="text-4xl sm:text-5xl font-extrabold mb-3">
                    Choose Your <span class="gradient-text">Plan</span>
                </h1>
                <p class="text-gray-400 text-lg">All plans auto-renew. Cancel anytime from your billing dashboard.</p>
            </div>

            {{-- Plans Grid --}}
            @if($plans->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-{{ min($plans->count(), 3) }} gap-6 mb-12">
                @foreach($plans as $plan)
                @php
                    $features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
                @endphp
                <div class="glass rounded-2xl p-6 {{ $plan->is_popular ? 'border-2 border-brand-500 relative' : '' }} transition-all hover:-translate-y-1">
                    @if($plan->is_popular)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="btn-primary text-white text-xs font-bold px-4 py-1.5 rounded-full">Recommended</span>
                    </div>
                    @endif

                    <h3 class="text-xl font-bold mb-1">{{ $plan->name }}</h3>
                    <p class="text-gray-400 text-xs mb-4">{{ $plan->description }}</p>

                    <div class="flex items-baseline gap-1 mb-4">
                        <span class="text-4xl font-black">${{ number_format($plan->price, 0) }}</span>
                        <span class="text-gray-400 text-sm">/{{ $plan->billing_cycle ?? 'month' }}</span>
                    </div>

                    <form action="/billing/checkout" method="POST" class="mb-4">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}" />
                        <button type="submit"
                                class="{{ $plan->is_popular ? 'btn-primary' : 'border border-brand-500/50 hover:border-brand-500' }} w-full text-white font-semibold text-sm px-4 py-3 rounded-xl transition-all">
                            @if($plan->stripe_price_id)
                                Upgrade to {{ $plan->name }}
                            @else
                                Contact Sales
                            @endif
                        </button>
                    </form>

                    <ul class="space-y-2 text-xs text-gray-400">
                        <li class="flex gap-2 items-center">
                            ✅ {{ $plan->max_users == -1 ? 'Unlimited' : $plan->max_users }} staff members
                        </li>
                        <li class="flex gap-2 items-center">
                            ✅ {{ $plan->max_appointments == -1 ? 'Unlimited' : number_format($plan->max_appointments) }} appt/mo
                        </li>
                        @if(is_array($features))
                            @foreach(array_slice($features, 0, 4) as $f)
                            <li class="flex gap-2 items-center">✅ {{ $f }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Billing History --}}
            @if($invoices && $invoices->count() > 0)
            <div class="glass rounded-2xl overflow-hidden mb-12">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-bold text-lg">Billing History</h2>
                </div>
                <div class="divide-y divide-white/5">
                    @foreach($invoices as $inv)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-white">{{ $inv->plan_name ?? 'Subscription' }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($inv->created_at)->format('M d, Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-white">${{ number_format($inv->amount_paid, 2) }}</div>
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $inv->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Help --}}
            <div class="text-center">
                <p class="text-gray-500 text-sm mb-4">Need help or have a special request?</p>
                <div class="flex justify-center gap-4">
                    <a href="https://wa.me/1234567890?text=Hi+Velora+I+need+help+with+my+subscription"
                       target="_blank"
                       class="flex items-center gap-2 bg-green-500/10 border border-green-500/30 text-green-400 font-semibold text-sm px-5 py-2.5 rounded-xl hover:bg-green-500/20 transition-all">
                        <span>💬</span> WhatsApp Chat
                    </a>
                    <a href="mailto:support@velora.com"
                       class="flex items-center gap-2 glass text-gray-300 font-semibold text-sm px-5 py-2.5 rounded-xl hover:text-white transition-all">
                        <span>✉️</span> Email Support
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
