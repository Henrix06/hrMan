<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'uMsizi HR') }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#4e45e4", "primary-container": "#6760fd", "primary-fixed": "#6760fd",
                        "primary-fixed-dim": "#5a52f0", "primary-dim": "#4135d8", "on-primary": "#fbf7ff",
                        "on-primary-fixed": "#ffffff", "on-primary-fixed-variant": "#edeaff",
                        "on-primary-container": "#000000", "secondary": "#526073", "secondary-dim": "#465467",
                        "secondary-container": "#d4e4fa", "secondary-fixed": "#d4e4fa",
                        "secondary-fixed-dim": "#c6d6ec", "on-secondary": "#f7f9ff",
                        "on-secondary-fixed": "#324052", "on-secondary-fixed-variant": "#4e5d6f",
                        "on-secondary-container": "#445365", "tertiary": "#745479", "tertiary-dim": "#67486c",
                        "tertiary-fixed": "#f5cdf9", "tertiary-fixed-dim": "#e7bfea",
                        "tertiary-container": "#f5cdf9", "on-tertiary": "#fff7fb",
                        "on-tertiary-fixed": "#4c2f52", "on-tertiary-fixed-variant": "#6a4b70",
                        "on-tertiary-container": "#604266", "error": "#a8364b", "error-dim": "#6b0221",
                        "error-container": "#f97386", "on-error": "#fff7f7", "on-error-container": "#6e0523",
                        "background": "#f7f9fb", "on-background": "#2c3437", "surface": "#f7f9fb",
                        "surface-dim": "#d4dbdf", "surface-bright": "#f7f9fb", "surface-tint": "#4e45e4",
                        "surface-variant": "#dce4e8", "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f0f4f7", "surface-container": "#eaeff2",
                        "surface-container-high": "#e3e9ed", "surface-container-highest": "#dce4e8",
                        "on-surface": "#2c3437", "on-surface-variant": "#596064", "outline": "#747c80",
                        "outline-variant": "#acb3b7", "inverse-surface": "#0b0f10",
                        "inverse-on-surface": "#9a9d9f", "inverse-primary": "#8582ff",
                    },
                    fontFamily: {
                        "headline": ["Manrope", "sans-serif"],
                        "body": ["Inter", "sans-serif"],
                        "label": ["Inter", "sans-serif"],
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel { background: rgba(255,255,255,0.4); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.2); }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .auth-input { @apply w-full h-14 px-5 rounded-xl bg-surface-container-low border-none focus:ring-1 focus:ring-primary focus:bg-surface-container-lowest text-on-surface transition-all outline-none placeholder:text-outline; }
        .auth-error { @apply mt-1.5 text-xs text-error; }
    </style>

    @livewireStyles
</head>
<body class="bg-background font-body text-on-background selection:bg-primary-container selection:text-on-primary-container">

    <!-- Top Nav -->
    <header class="fixed top-0 left-0 right-0 h-20 px-8 md:px-16 flex justify-between items-center z-50 bg-white/60 backdrop-blur-2xl">
        <a href="/" class="flex items-center gap-2">
            <span class="text-xl font-bold tracking-tight text-indigo-700 font-headline">uMsizi</span>
        </a>
        <nav class="hidden md:flex gap-8 items-center">
            <span class="text-sm font-medium text-slate-400">HR Platform</span>
        </nav>
        <div class="flex items-center gap-4">
            @if (Route::has('login'))
                <a href="{{ route('login') }}" wire:navigate
                    class="text-sm font-medium text-indigo-600 transition-opacity hover:opacity-80">Login</a>
            @endif
            @if (Route::has('register'))
                <a href="{{ route('register') }}" wire:navigate
                    class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Sign Up</a>
            @endif
        </div>
    </header>

    <main class="min-h-screen flex items-stretch pt-20">
        <!-- Left panel: decorative -->
        <section class="hidden lg:flex flex-1 relative overflow-hidden bg-surface-container items-center justify-center p-12">
            <div class="absolute inset-0 z-0">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAUQIoKDz9IhsxxTLFA4BHAKy06_pzu0uxzbB02v5xfqRvKaEbUcHUQU0r7Vr83-LjKfQJtxqJ9kZask3dBbuJVFv8FEnQHSTeJK4WCvcxEvFEzhj6ftoaAjY0ge3A2r3g885IDtrCiJSxN1XZrkdsR5urGIIyZ3L8U9xBNgYfk2L1SWseP-lXA_HbUZOudMu8x2_Zu55VSqzbQen7DqTzBGZHm4eeZIG-pa_SEtUWhXTXqVUNLH70MKZHj0PSH3KiP_QpUbwtgbuA"
                    alt="Abstract HR background" class="w-full h-full object-cover opacity-60"/>
            </div>
            <div class="relative z-10 w-full max-w-xl">
                <div class="glass-panel p-12 rounded-[2rem] shadow-2xl space-y-8">
                    <div class="space-y-4">
                        <h1 class="font-headline text-5xl font-extrabold text-on-surface leading-tight tracking-tight">
                            The Fluid <br/><span class="text-primary italic">Professional</span>
                        </h1>
                        <p class="text-on-surface-variant text-lg leading-relaxed max-w-md">
                            Experience the future of human resources. uMsizi brings clarity and elegance to your organization's workflow.
                        </p>
                    </div>
                    <div class="bg-white/40 p-6 rounded-2xl border border-white/20 inline-flex items-center gap-4 shadow-sm backdrop-blur-md">
                        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white">
                            <span class="material-symbols-outlined">auto_awesome</span>
                        </div>
                        <div>
                            <p class="text-xs font-label text-on-surface-variant font-medium">Platform Reliability</p>
                            <p class="text-xl font-headline font-bold text-on-surface">99.9% Uptime</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="absolute top-20 left-20 w-64 h-64 bg-tertiary/20 rounded-full blur-[100px]"></div>
            <div class="absolute bottom-20 right-20 w-80 h-80 bg-primary/20 rounded-full blur-[120px]"></div>
        </section>

        <!-- Right panel: form slot -->
        <section class="flex-1 flex flex-col justify-center items-center px-6 md:px-12 bg-surface-container-lowest overflow-y-auto py-12">
            <div class="w-full max-w-md">
                <!-- Mobile logo -->
                <div class="lg:hidden flex flex-col items-center gap-2 mb-10">
                    <span class="text-3xl font-bold tracking-tight text-indigo-700 font-headline">uMsizi</span>
                    <span class="text-on-surface-variant font-label text-sm">The Fluid Professional Workspace</span>
                </div>

                {{ $slot }}
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="w-full px-8 md:px-16 flex flex-col md:flex-row justify-between items-center gap-6 py-10 bg-slate-50 border-t border-slate-200/50">
        <div class="flex flex-col gap-1">
            <span class="text-lg font-bold text-indigo-700 font-headline">uMsizi</span>
            <p class="text-sm text-slate-500">© {{ date('Y') }} uMsizi. The Fluid Professional Workspace.</p>
        </div>
        <div class="flex gap-8">
            <a href="#" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">Privacy Policy</a>
            <a href="#" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">Terms of Service</a>
            <a href="#" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">Security</a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
