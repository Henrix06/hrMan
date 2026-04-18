<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pricing — {{ config('app.name', 'uMsizi') }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4e45e4',
                        'primary-dim': '#4135d8',
                        surface: '#f7f9fb',
                        'on-surface': '#2c3437',
                        'on-surface-variant': '#596064',
                        'outline-variant': '#acb3b7',
                    },
                    fontFamily: {
                        headline: ['Manrope', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .font-headline { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-style: normal; font-weight: normal;
            display: inline-block; line-height: 1;
        }
        .filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="bg-surface text-on-surface min-h-screen antialiased">

    {{-- Nav --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="font-headline font-black text-xl text-slate-900 tracking-tight">
                uMsizi<span class="text-primary">.</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-primary transition-colors">Sign in</a>
                <a href="{{ route('register') }}" class="text-sm bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dim transition-colors">
                    Get started
                </a>
            </div>
        </div>
    </nav>

    <main class="pt-16">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-100 py-10 mt-20">
        <div class="max-w-7xl mx-auto px-6 text-center text-sm text-slate-400">
            © {{ date('Y') }} uMsizi HR. All rights reserved.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
