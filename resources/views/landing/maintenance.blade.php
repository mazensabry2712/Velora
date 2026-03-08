<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $appName ?? 'Velora' }} — Under Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#6C63FF',
                            600: '#5b4ff7',
                        },
                        surface: '#0f0e1a',
                    },
                    animation: {
                        'pulse-slow': 'pulse 4s ease-in-out infinite',
                        'fade-up': 'fadeUp 0.6s ease both',
                        'spin-slow': 'spin 8s linear infinite',
                    },
                    keyframes: {
                        fadeUp: {
                            '0%': { opacity: '0', transform: 'translateY(24px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #6C63FF 0%, #a78bfa 50%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-bg {
            background:
                radial-gradient(ellipse 90% 60% at 50% -5%, rgba(108,99,255,0.28) 0%, transparent 65%),
                radial-gradient(ellipse 50% 40% at 80% 50%, rgba(56,189,248,0.08) 0%, transparent 60%),
                #0f0e1a;
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gear-ring {
            border: 2px dashed rgba(108,99,255,0.3);
        }
    </style>
</head>
<body class="bg-surface text-white antialiased">

<div class="hero-bg min-h-screen flex flex-col items-center justify-center px-4 relative overflow-hidden">

    <!-- Background blobs -->
    <div class="absolute top-1/4 left-10 w-72 h-72 bg-brand-500/10 rounded-full blur-3xl animate-pulse-slow pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-sky-500/8 rounded-full blur-3xl animate-pulse-slow pointer-events-none" style="animation-delay:2s"></div>

    <div class="relative z-10 text-center max-w-2xl mx-auto">

        <!-- Logo -->
        <div class="flex items-center justify-center gap-2 mb-10 animate-fade-up">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, #6C63FF 0%, #8b76ff 100%); box-shadow: 0 8px 30px rgba(108,99,255,0.4);">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-2xl font-bold text-white tracking-tight">{{ $appName ?? 'Velora' }}</span>
        </div>

        <!-- Animated gear icon -->
        <div class="flex items-center justify-center mb-8 animate-fade-up" style="animation-delay:0.1s">
            <div class="relative w-24 h-24">
                <!-- Outer ring -->
                <div class="absolute inset-0 rounded-full gear-ring animate-spin-slow opacity-60"></div>
                <!-- Inner circle -->
                <div class="absolute inset-3 rounded-full flex items-center justify-center"
                     style="background: rgba(108,99,255,0.15); border: 1px solid rgba(108,99,255,0.3);">
                    <svg class="w-10 h-10 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Heading -->
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight mb-4 animate-fade-up" style="animation-delay:0.2s">
            We'll be back <span class="gradient-text">shortly</span>
        </h1>

        <!-- Subtitle -->
        <p class="text-lg sm:text-xl text-gray-400 mb-8 leading-relaxed animate-fade-up" style="animation-delay:0.3s">
            {{ $appName ?? 'Velora' }} is currently undergoing scheduled maintenance.<br class="hidden sm:inline"/>
            We're working hard to improve your experience.
        </p>

        <!-- Status badge -->
        <div class="inline-flex items-center gap-2 glass rounded-full px-4 py-2 text-sm text-gray-300 animate-fade-up" style="animation-delay:0.4s">
            <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse flex-shrink-0"></span>
            <span>Maintenance in progress — please check back soon</span>
        </div>

    </div>

</div>

</body>
</html>
