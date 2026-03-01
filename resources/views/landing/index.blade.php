@extends('layouts.landing')

@push('styles')
<style>
    .hero-bg {
        background:
            radial-gradient(ellipse 90% 60% at 50% -5%, rgba(108,99,255,0.28) 0%, transparent 65%),
            radial-gradient(ellipse 50% 40% at 80% 50%, rgba(56,189,248,0.1) 0%, transparent 60%),
            #0f0e1a;
    }
    .feature-icon {
        background: linear-gradient(135deg, rgba(108,99,255,0.2) 0%, rgba(139,118,255,0.1) 100%);
        border: 1px solid rgba(108,99,255,0.3);
    }
    .pricing-popular {
        background: linear-gradient(135deg, rgba(108,99,255,0.15) 0%, rgba(56,189,248,0.08) 100%);
        border: 2px solid #6C63FF !important;
    }
    .stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(108,99,255,0.05) 100%);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .step-connector::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 100%;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, rgba(108,99,255,0.5), transparent);
        transform: translateY(-50%);
    }
    .testimonial-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(108,99,255,0.04) 100%);
    }
    .animate-delay-1 { animation-delay: 0.1s; }
    .animate-delay-2 { animation-delay: 0.2s; }
    .animate-delay-3 { animation-delay: 0.3s; }
    .animate-delay-4 { animation-delay: 0.4s; }
    .animate-delay-5 { animation-delay: 0.5s; }

    .ticker-wrap {
        overflow: hidden;
        white-space: nowrap;
    }
    .ticker {
        display: inline-block;
        animation: ticker 20s linear infinite;
    }
    @keyframes ticker {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════════════════════ --}}
<section class="hero-bg min-h-screen flex flex-col items-center justify-center pt-20 pb-16 relative overflow-hidden">

    {{-- Background blobs --}}
    <div class="absolute top-1/4 left-10 w-72 h-72 bg-brand-500/10 rounded-full blur-3xl animate-pulse-slow pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-sky-500/8 rounded-full blur-3xl animate-pulse-slow pointer-events-none" style="animation-delay:2s"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 glass rounded-full px-4 py-1.5 text-sm text-gray-300 mb-8 animate-fade-up">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
            Now with AI-powered scheduling • 14-day free trial
        </div>

        {{-- Headline --}}
        <h1 class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-extrabold tracking-tight mb-6 animate-fade-up animate-delay-1 leading-[1.05]">
            The Smarter Way<br />
            to <span class="gradient-text">Book & Manage</span>
        </h1>

        {{-- Subheadline --}}
        <p class="text-xl sm:text-2xl text-gray-400 mb-10 max-w-3xl mx-auto leading-relaxed animate-fade-up animate-delay-2">
            Velora gives your business a powerful appointment & queue management system.
            Set it up in minutes. Delight every customer.
        </p>

        {{-- CTA Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16 animate-fade-up animate-delay-3">
            <a href="{{ route('signup') }}"
               class="btn-primary text-white font-bold text-lg px-8 py-4 rounded-2xl inline-flex items-center justify-center gap-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Start Free 14-Day Trial
            </a>
            <a href="#how-it-works"
               class="glass text-gray-300 hover:text-white font-semibold text-lg px-8 py-4 rounded-2xl inline-flex items-center justify-center gap-2 transition-all hover:border-brand-500/50">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                See How It Works
            </a>
        </div>

        {{-- Trust signals --}}
        <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-500 animate-fade-up animate-delay-4 mb-16">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                No credit card required
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                Setup in under 5 minutes
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                Cancel anytime
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                10 languages supported
            </div>
        </div>

        {{-- Dashboard Preview --}}
        <div class="animate-fade-up animate-delay-5 relative max-w-5xl mx-auto">
            <div class="absolute inset-0 bg-brand-500/20 blur-3xl rounded-3xl pointer-events-none"></div>
            <div class="relative glass rounded-2xl overflow-hidden border border-white/10 animate-float shadow-2xl">
                {{-- Fake Browser Chrome --}}
                <div class="bg-white/5 px-4 py-3 flex items-center gap-2 border-b border-white/5">
                    <div class="w-3 h-3 rounded-full bg-red-400/70"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-400/70"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400/70"></div>
                    <div class="flex-1 mx-4 bg-white/5 rounded-md px-3 py-1 text-xs text-gray-500 text-center font-mono">
                        mysalon.velora.com/admin/dashboard
                    </div>
                </div>
                {{-- Dashboard Preview Grid --}}
                <div class="p-6 bg-gray-900/60">
                    <div class="grid grid-cols-4 gap-3 mb-4">
                        @foreach([['📅','Today\'s Appointments','24', '+12%'],['👥','Active Queue','8','Live'],['⭐','Avg Rating','4.9','★★★★★'],['💰','Revenue','$1,240','+18%']] as [$icon, $label, $val, $sub])
                        <div class="glass rounded-xl p-3 text-left">
                            <div class="text-lg mb-1">{{ $icon }}</div>
                            <div class="text-gray-400 text-xs mb-1">{{ $label }}</div>
                            <div class="text-white font-bold text-base">{{ $val }}</div>
                            <div class="text-brand-400 text-xs">{{ $sub }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2 glass rounded-xl p-3">
                            <div class="text-xs text-gray-400 mb-2">Weekly Appointments</div>
                            <div class="flex items-end gap-1 h-16">
                                @foreach([30,50,40,70,60,85,45] as $h)
                                <div class="flex-1 rounded-sm bg-brand-500/60" style="height:{{ $h }}%"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="glass rounded-xl p-3">
                            <div class="text-xs text-gray-400 mb-2">Queue Status</div>
                            @foreach([['A-01','💇 Sarah K.','Serving'],['A-02','💅 Mike R.','Waiting'],['A-03','✂️ John D.','Waiting']] as [$n,$nm,$st])
                            <div class="flex items-center justify-between py-1 border-b border-white/5 last:border-0">
                                <span class="text-xs font-mono text-brand-400">{{ $n }}</span>
                                <span class="text-xs text-gray-300">{{ $nm }}</span>
                                <span class="text-xs {{ $st === 'Serving' ? 'text-green-400' : 'text-gray-500' }}">{{ $st }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     STATS TICKER
══════════════════════════════════════════════════════════════════════════ --}}
<div class="border-y border-white/5 py-5 bg-white/[0.02]">
    <div class="ticker-wrap">
        <div class="ticker">
            @php
                $items = ['500+ Businesses', 'Trusted Globally', '10 Languages', '14-Day Free Trial', 'Real-Time Queue', 'Smart Scheduling', 'Unlimited Customization', 'Instant Setup', '99.9% Uptime', 'Enterprise Security', '500+ Businesses', 'Trusted Globally', '10 Languages', '14-Day Free Trial', 'Real-Time Queue', 'Smart Scheduling'];
            @endphp
            @foreach($items as $item)
                <span class="inline-flex items-center gap-3 mx-8 text-gray-400 text-sm font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                    {{ $item }}
                </span>
            @endforeach
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     LOGOS / USED BY
══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <p class="text-gray-500 text-sm font-medium mb-8 uppercase tracking-widest">Trusted by businesses in 30+ countries</p>
    <div class="flex flex-wrap justify-center items-center gap-8 opacity-40 grayscale">
        @foreach(['Salon Pro','MediBook','BarberHub','SpaSync','ClinicFlow','NailArt Studio'] as $logo)
        <div class="glass px-6 py-3 rounded-xl text-white font-bold text-sm tracking-tight">{{ $logo }}</div>
        @endforeach
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     FEATURES
══════════════════════════════════════════════════════════════════════════ --}}
<section id="features" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">Features</span>
        <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
            Everything your business <span class="gradient-text">needs</span>
        </h2>
        <p class="text-xl text-gray-400 max-w-2xl mx-auto">
            From solo freelancers to enterprise chains — Velora scales with you.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
        $features = [
            ['📅', 'Smart Scheduling',        'Intelligent appointment booking with automatic conflict detection, buffer times, and working hours management.',   'Always organized'],
            ['🎯', 'Real-Time Queue',          'Manage walk-ins with a live digital queue. Customers track their position from their phones via their unique QR code.', 'Zero waiting confusion'],
            ['👥', 'Staff Management',         'Assign services to staff, set individual schedules, track performance, and manage permissions per role.',         'Team in sync'],
            ['📊', 'Analytics & Reports',      'Detailed insights on appointments, revenue, peak hours, customer ratings, and staff performance.',               'Data-driven decisions'],
            ['🌍', 'Multi-Language',           'Support 10 languages out of the box: Arabic, English, French, Spanish, German, and more. Per-tenant language control.', 'Global reach'],
            ['🔔', 'Smart Reminders',          'Automated email & SMS reminders reduce no-shows. Customizable timing and message templates per business.',      'Fewer no-shows'],
            ['⭐', 'Customer Ratings',         'Collect post-appointment ratings automatically. Build your reputation and identify improvement areas.',          'Quality feedback'],
            ['🛡️', 'Enterprise Security',     'Isolated database per tenant, SSL everywhere, role-based access, audit logs, and GDPR-compliant data handling.',  'Peace of mind'],
            ['⚡', 'Lightning Fast Setup',    'Go from signup to fully operational in under 5 minutes. No technical knowledge required.',                       'Live in minutes'],
        ];
        @endphp

        @foreach($features as [$icon, $title, $desc, $tag])
        <div class="glass rounded-2xl p-6 card-hover group">
            <div class="feature-icon w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                {{ $icon }}
            </div>
            <h3 class="text-lg font-bold text-white mb-2">{{ $title }}</h3>
            <p class="text-gray-400 text-sm leading-relaxed mb-4">{{ $desc }}</p>
            <span class="inline-block text-xs font-semibold text-brand-400 bg-brand-500/10 px-3 py-1 rounded-full">
                {{ $tag }}
            </span>
        </div>
        @endforeach
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════════════════════════════════════════ --}}
<section id="how-it-works" class="py-24 bg-white/[0.02] border-y border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">How It Works</span>
            <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
                Up and running in <span class="gradient-text">3 simple steps</span>
            </h2>
            <p class="text-xl text-gray-400">No technical knowledge required. Seriously.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
            @php
            $steps = [
                ['01', '📝', 'Create Your Account',    'Sign up with your business name and choose your unique subdomain (e.g., mysalon.velora.com). Takes 60 seconds.'],
                ['02', '⚙️', 'Configure Your Business', 'Add your services, set working hours, invite your staff, and customize your booking page. It\'s all visual.'],
                ['03', '🚀', 'Go Live & Grow',           'Share your booking link with customers. Start accepting appointments immediately. Watch your business grow.'],
            ];
            @endphp

            @foreach($steps as $i => [$num, $icon, $title, $desc])
            <div class="relative group">
                {{-- Connector arrow --}}
                @if(!$loop->last)
                <div class="hidden md:block absolute top-12 left-full w-full z-10 pointer-events-none">
                    <div class="h-px bg-gradient-to-r from-brand-500/50 to-transparent w-full"></div>
                    <div class="absolute right-0 top-1/2 -translate-y-1/2 border-r-4 border-t-4 border-brand-500/50 w-3 h-3 rotate-45"></div>
                </div>
                @endif

                <div class="glass rounded-2xl p-8 card-hover h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl btn-primary flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            {{ $icon }}
                        </div>
                        <span class="text-6xl font-black text-brand-500/20 leading-none">{{ $num }}</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">{{ $title }}</h3>
                    <p class="text-gray-400 leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('signup') }}"
               class="btn-primary text-white font-bold text-lg px-10 py-4 rounded-2xl inline-flex items-center gap-3">
                Start Your Free Trial Now
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     PRICING PREVIEW
══════════════════════════════════════════════════════════════════════════ --}}
<section id="pricing" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">Pricing</span>
        <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
            Simple, <span class="gradient-text">transparent</span> pricing
        </h2>
        <p class="text-xl text-gray-400 mb-2">Start free. Scale when you're ready.</p>
        <p class="text-brand-400 font-semibold">🎉 All plans include a {{ $maxTrialDays }}-day free trial — no credit card required</p>
    </div>

    @if($plans->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-{{ min($plans->count(), 3) }} gap-6 max-w-5xl mx-auto">
        @foreach($plans->take(3) as $plan)
        @php
            $features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
        @endphp
        <div class="glass rounded-2xl p-8 card-hover {{ $plan->is_popular ? 'pricing-popular relative' : '' }}">
            @if($plan->is_popular)
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="btn-primary text-white text-xs font-bold px-4 py-1.5 rounded-full">Most Popular</span>
            </div>
            @endif

            <h3 class="text-xl font-bold text-white mb-1">{{ $plan->name }}</h3>
            <p class="text-gray-400 text-sm mb-6">{{ $plan->description }}</p>

            <div class="mb-6">
                <div class="flex items-baseline gap-1">
                    <span class="text-5xl font-black text-white">${{ number_format($plan->price, 0) }}</span>
                    <span class="text-gray-400 text-sm">/{{ $plan->billing_cycle === 'yearly' ? 'year' : 'month' }}</span>
                </div>
                @if($plan->billing_cycle === 'yearly')
                <p class="text-green-400 text-xs mt-1">Save 20% vs monthly</p>
                @endif
            </div>

            <a href="{{ route('signup') }}?plan={{ $plan->id }}"
               class="{{ $plan->is_popular ? 'btn-primary' : 'glass border border-brand-500/40 hover:border-brand-500' }} text-white font-semibold text-sm px-6 py-3 rounded-xl block text-center mb-6 transition-all">
                @if($plan->trial_days > 0)
                    Start {{ $plan->trial_days }}-Day Free Trial
                @else
                    Get Started
                @endif
            </a>

            <ul class="space-y-2.5">
                <li class="flex items-center gap-2 text-sm text-gray-300">
                    <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                    </svg>
                    {{ $plan->max_users == -1 ? 'Unlimited' : $plan->max_users }} staff members
                </li>
                <li class="flex items-center gap-2 text-sm text-gray-300">
                    <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                    </svg>
                    {{ $plan->max_appointments == -1 ? 'Unlimited' : number_format($plan->max_appointments) }} appointments/mo
                </li>
                @if(is_array($features))
                    @foreach(array_slice($features, 0, 5) as $feature)
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                @endif
            </ul>
        </div>
        @endforeach
    </div>
    @else
    {{-- Placeholder pricing if no plans in DB --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        @foreach([
            ['Starter','9','5 staff','500 appt/mo', false],
            ['Professional','29','20 staff','Unlimited appt', true],
            ['Enterprise','79','Unlimited staff','Unlimited everything', false],
        ] as [$name, $price, $users, $appts, $popular])
        <div class="glass rounded-2xl p-8 card-hover {{ $popular ? 'pricing-popular relative' : '' }}">
            @if($popular)
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="btn-primary text-white text-xs font-bold px-4 py-1.5 rounded-full">Most Popular</span>
            </div>
            @endif
            <h3 class="text-xl font-bold mb-1">{{ $name }}</h3>
            <div class="flex items-baseline gap-1 my-4">
                <span class="text-5xl font-black">${{ $price }}</span>
                <span class="text-gray-400 text-sm">/month</span>
            </div>
            <a href="{{ route('signup') }}"
               class="{{ $popular ? 'btn-primary' : 'glass border border-brand-500/40 hover:border-brand-500' }} text-white font-semibold text-sm px-6 py-3 rounded-xl block text-center mb-6 transition-all">
                Start Free Trial
            </a>
            <ul class="space-y-2.5 text-sm text-gray-300">
                <li class="flex gap-2 items-center">✅ {{ $users }}</li>
                <li class="flex gap-2 items-center">✅ {{ $appts }}</li>
                <li class="flex gap-2 items-center">✅ Queue Management</li>
                <li class="flex gap-2 items-center">✅ Analytics Dashboard</li>
                <li class="flex gap-2 items-center">✅ Email Reminders</li>
            </ul>
        </div>
        @endforeach
    </div>
    @endif

    <div class="text-center mt-10">
        <a href="{{ route('pricing') }}" class="text-brand-400 hover:text-brand-300 text-sm font-semibold inline-flex items-center gap-1 transition-colors">
            View full pricing comparison →
        </a>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════════════════════════════════════════ --}}
<section id="testimonials" class="py-24 bg-white/[0.02] border-y border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">Testimonials</span>
            <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
                Loved by <span class="gradient-text">businesses worldwide</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
            $testimonials = [
                ['⭐⭐⭐⭐⭐', '"Velora completely transformed how we manage our salon. Our no-show rate dropped by 60% and customers love the queue tracking."', 'Sarah Al-Rashidi', 'Salon Owner, Dubai 🇦🇪'],
                ['⭐⭐⭐⭐⭐', '"Setup took literally 4 minutes. We had appointments flowing in the same day. The multi-language support is perfect for our diverse clientele."', 'Marco Fernandez', 'Barbershop Owner, Madrid 🇪🇸'],
                ['⭐⭐⭐⭐⭐', '"The analytics alone are worth it. We can now see our busiest hours, best-performing staff, and customer satisfaction trends."', 'Wei Zhang', 'Clinic Manager, Shanghai 🇨🇳'],
                ['⭐⭐⭐⭐⭐', '"Finally a booking system that works for Arabic speakers! The RTL support is flawless. Our customers appreciate it deeply."', 'Ahmed Hassan', 'Spa Director, Cairo 🇪🇬'],
                ['⭐⭐⭐⭐⭐', '"Migrated from a $500/month enterprise tool to Velora. Same features for a fraction of the price. Absolutely no regrets."', 'Priya Sharma', 'MedSpa Owner, London 🇬🇧'],
                ['⭐⭐⭐⭐⭐', '"The QR code queue system is genius. Customers scan on arrival and wait comfortably without crowding our reception."', 'Lucas Weber', 'Clinic Owner, Berlin 🇩🇪'],
            ];
            @endphp

            @foreach($testimonials as [$stars, $quote, $name, $role])
            <div class="testimonial-card glass rounded-2xl p-6 card-hover">
                <div class="text-sm mb-3">{{ $stars }}</div>
                <p class="text-gray-300 text-sm leading-relaxed mb-4 italic">{{ $quote }}</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full btn-primary flex items-center justify-center text-sm font-bold text-white">
                        {{ strtoupper(substr($name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-white">{{ $name }}</div>
                        <div class="text-xs text-gray-500">{{ $role }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     FAQ
══════════════════════════════════════════════════════════════════════════ --}}
<section id="faq" class="py-24 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">FAQ</span>
        <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
            Questions? <span class="gradient-text">Answered.</span>
        </h2>
    </div>

    <div class="space-y-4" id="faqList">
        @php
        $faqs = [
            ['Do I need a credit card to start?',                      'No! Start your 14-day free trial with just your email. No credit card info required until you decide to subscribe.'],
            ['What happens after my trial ends?',                      'After 14 days, you get a 3-day grace period. During grace, you can still access your data. After that, your account enters read-only mode until you upgrade. Nothing is deleted.'],
            ['Can I cancel anytime?',                                  'Absolutely. You can cancel your subscription at any time from your billing dashboard. No lock-in contracts, no cancellation fees.'],
            ['How does the subdomain work?',                           'When you sign up, you choose a unique subdomain (e.g., yoursalon.velora.com). This is your dedicated booking URL to share with customers.'],
            ['Is my data isolated from other businesses?',             'Yes! Each tenant gets their own dedicated database. Your business data is completely isolated and never mixed with other tenants.'],
            ['Can I use my own custom domain?',                        'Custom domain support is available on Professional and Enterprise plans. Contact support to set up your custom domain.'],
            ['What languages are supported?',                          'Velora supports 10 languages: Arabic, English, French, Spanish, German, Italian, Portuguese, Russian, Chinese, and Japanese. Each tenant can configure which languages their customers can use.'],
            ['How many staff members can I add?',                      'Depends on your plan. Starter allows up to 5 staff, Professional up to 20, and Enterprise allows unlimited staff members.'],
        ];
        @endphp

        @foreach($faqs as $i => [$q, $a])
        <div class="glass rounded-xl overflow-hidden">
            <button
                onclick="toggleFaq({{ $i }})"
                class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-white/5 transition-colors"
            >
                <span class="font-semibold text-white text-sm pr-4">{{ $q }}</span>
                <svg id="faqIcon{{ $i }}" class="w-5 h-5 text-brand-400 flex-shrink-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="faqAnswer{{ $i }}" class="hidden px-6 pb-4">
                <p class="text-gray-400 text-sm leading-relaxed">{{ $a }}</p>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     CTA SECTION
══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-radial from-brand-500/20 via-transparent to-transparent pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="glass rounded-3xl p-12 border border-brand-500/30">
            <div class="text-6xl mb-6">🚀</div>
            <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
                Ready to <span class="gradient-text">transform</span> your business?
            </h2>
            <p class="text-xl text-gray-400 mb-8 max-w-2xl mx-auto">
                Join thousands of businesses that trust Velora to manage their appointments and queues.
                Start your free trial today — no credit card needed.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('signup') }}"
                   class="btn-primary text-white font-bold text-lg px-10 py-4 rounded-2xl inline-flex items-center justify-center gap-3">
                    🎉 Start Free 14-Day Trial
                </a>
                <a href="{{ route('pricing') }}"
                   class="glass text-gray-300 hover:text-white font-semibold text-lg px-10 py-4 rounded-2xl inline-flex items-center justify-center gap-2 transition-all">
                    View Pricing →
                </a>
            </div>
            <p class="text-gray-600 text-sm mt-6">No credit card • 14-day free trial • Cancel anytime</p>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
function toggleFaq(index) {
    const answer = document.getElementById('faqAnswer' + index);
    const icon   = document.getElementById('faqIcon' + index);
    const isOpen = !answer.classList.contains('hidden');

    // Close all
    document.querySelectorAll('[id^="faqAnswer"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="faqIcon"]').forEach(el => el.classList.remove('rotate-180'));

    if (!isOpen) {
        answer.classList.remove('hidden');
        icon.classList.add('rotate-180');
    }
}
</script>
@endpush
