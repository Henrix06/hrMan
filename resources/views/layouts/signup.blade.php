<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Get Started — {{ config('app.name', 'uMsizi HR') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"/>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4e45e4',
                        'primary-container': '#6760fd',
                        'primary-dim': '#4135d8',
                        'on-primary': '#fbf7ff',
                        surface: '#f7f9fb',
                        'surface-container-low': '#f0f4f7',
                        'on-surface': '#2c3437',
                        'on-surface-variant': '#596064',
                        outline: '#747c80',
                        'outline-variant': '#acb3b7',
                        error: '#a8364b',
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
        .auth-input {
            width: 100%; height: 3.25rem; padding: 0 1.25rem;
            border-radius: 0.75rem;
            background: #f0f4f7;
            border: 1.5px solid transparent;
            font-size: 0.875rem;
            color: #2c3437;
            outline: none;
            transition: all 0.15s;
        }
        .auth-input:focus {
            background: #ffffff;
            border-color: #4e45e4;
            box-shadow: 0 0 0 3px rgba(78,69,228,0.12);
        }
        .auth-input::placeholder { color: #acb3b7; }
        .auth-input.error { border-color: #a8364b; background: #fff5f7; }
        .auth-error { margin-top: 0.375rem; font-size: 0.75rem; color: #a8364b; }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="bg-white text-on-surface min-h-screen antialiased">

<div class="min-h-screen flex">

    {{-- ── Left panel: plan summary ── --}}
    <div class="hidden lg:flex flex-col w-[38%] bg-slate-900 relative overflow-hidden p-10">

        {{-- Logo --}}
        <a href="/" class="font-headline font-bold text-xl text-white tracking-tight">
            uMsizi<span class="text-primary">.</span>
        </a>

        {{-- Content --}}
        <div class="flex-1 flex flex-col justify-center">
            @isset($sidebar)
                {{ $sidebar }}
            @endisset
        </div>

        {{-- Decorative blobs --}}
        <div class="absolute top-20 right-0 w-64 h-64 bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-10 left-0 w-48 h-48 bg-purple-500/10 rounded-full blur-[80px] pointer-events-none"></div>
    </div>

    {{-- ── Right panel: form ── --}}
    <div class="flex-1 flex flex-col justify-center items-center px-6 md:px-12 py-12 bg-white overflow-y-auto">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 text-center">
            <div class="font-headline font-bold text-2xl text-slate-900">uMsizi<span class="text-primary">.</span></div>
        </div>

        <div class="w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>

</div>

@livewireScripts
</body>
</html>
