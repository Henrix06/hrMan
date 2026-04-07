<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
    x-init="$watch('dark', v => { localStorage.setItem('theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v) }); document.documentElement.classList.toggle('dark', dark)"
    :class="dark ? 'dark' : ''"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Ethereal HR') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com"/>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; color: #2c3437; }
            .dark body { background-color: #0f1117; color: #e2e8f0; }
            .glass-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.4); }
            .dark .glass-card { background: rgba(30,34,46,0.75); border-color: rgba(255,255,255,0.06); }
            .text-headline { font-family: 'Manrope', sans-serif; }
            .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
            [x-cloak] { display: none !important; }
        </style>

        @livewireStyles
    </head>
    <body class="min-h-screen antialiased bg-background dark:bg-[#0f1117] dark:text-slate-200 transition-colors duration-200">

        <livewire:layout.navigation />

        <!-- Page Content -->
        <main class="ml-64 pt-24 px-8 pb-12 bg-background dark:bg-[#0f1117] min-h-screen transition-colors duration-200">
            {{ $slot }}
        </main>

        <!-- Global Chat Overlay -->
        <livewire:chat.chat-box />

        @livewireScripts
    </body>
</html>
