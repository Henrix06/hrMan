<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'uMsizi') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
        <link rel="alternate icon" href="/favicon.ico"/>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com"/>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; color: #2c3437; }
            .glass-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.4); }
            .text-headline { font-family: 'Manrope', sans-serif; }
            .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
            [x-cloak] { display: none !important; }
        </style>

        @livewireStyles
    </head>
    <body class="min-h-screen antialiased bg-background transition-colors duration-200">

        <livewire:layout.navigation />

        <!-- Page Content -->
        <main class="ml-64 pt-24 px-8 pb-12 bg-background min-h-screen">
            {{ $slot }}
        </main>

        <!-- Global Chat Overlay -->
        <livewire:chat.chat-box />

        @livewireScripts
    </body>
</html>
