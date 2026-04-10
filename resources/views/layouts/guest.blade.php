<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'uMsizi HR') }}</title>

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
                        'on-primary-fixed-variant': '#edeaff',
                        surface: '#f7f9fb',
                        'surface-container-low': '#f0f4f7',
                        'surface-container': '#eaeff2',
                        'surface-container-lowest': '#ffffff',
                        'surface-variant': '#dce4e8',
                        'on-surface': '#2c3437',
                        'on-surface-variant': '#596064',
                        outline: '#747c80',
                        'outline-variant': '#acb3b7',
                        error: '#a8364b',
                    },
                    fontFamily: {
                        headline: ['Manrope', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                        label: ['Inter', 'sans-serif'],
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
        .auth-error { margin-top: 0.375rem; font-size: 0.75rem; color: #a8364b; }
        .left-panel-bg {
            background: radial-gradient(ellipse 80% 70% at 30% 40%, rgba(78,69,228,0.15) 0%, transparent 60%),
                        radial-gradient(ellipse 60% 50% at 80% 80%, rgba(116,84,121,0.1) 0%, transparent 55%),
                        #f0f0ff;
        }
    </style>

    @livewireStyles
</head>
<body class="bg-white text-on-surface min-h-screen">

<div class="min-h-screen flex">

    {{-- ── Left decorative panel ── --}}
    <div class="hidden lg:flex flex-col w-[52%] left-panel-bg relative overflow-hidden p-12">

        {{-- Logo --}}
        <a href="/" class="font-headline font-bold text-xl text-slate-900 tracking-tight">
            uMsizi<span class="text-primary">.</span>
        </a>

        {{-- Center content --}}
        <div class="flex-1 flex flex-col justify-center max-w-md">
            <div class="mb-8">
                <span class="inline-flex items-center gap-2 bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full border border-primary/20 mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                    HR Platform
                </span>
                <h1 class="font-headline font-black text-5xl text-slate-900 leading-[1.05] tracking-tight mb-5">
                    The Fluid<br/><span class="text-primary italic">Professional</span><br/>Workspace.
                </h1>
                <p class="text-slate-500 text-base leading-relaxed">
                    Payroll, leave, assets, and team management — all in one elegant interface built for modern organisations.
                </p>
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 gap-3 mb-8">
                @foreach([['groups','142 Employees','Active workforce'],['payments','$48k Payroll','This month'],['calendar_today','8 On Leave','Approved'],['checklist','23 Tasks','In progress']] as [$icon,$val,$sub])
                <div class="bg-white/70 backdrop-blur-sm rounded-2xl p-4 border border-white/60 shadow-sm">
                    <span class="material-symbols-outlined text-primary text-[18px] mb-2 block">{{ $icon }}</span>
                    <div class="font-headline font-bold text-sm text-slate-900">{{ $val }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $sub }}</div>
                </div>
                @endforeach
            </div>

            {{-- Testimonial --}}
            <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-5 border border-white/60 shadow-sm">
                <div class="flex items-center gap-1 mb-3">
                    @for($i=0;$i<5;$i++)
                    <span class="material-symbols-outlined text-amber-400 text-[14px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">star</span>
                    @endfor
                </div>
                <p class="text-slate-600 text-sm leading-relaxed italic mb-3">
                    "uMsizi transformed how we manage our team. It's the HR tool we always wished existed."
                </p>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-purple-500 flex items-center justify-center text-white text-xs font-bold">ER</div>
                    <div>
                        <div class="text-xs font-semibold text-slate-800">Elena Rodriguez</div>
                        <div class="text-[10px] text-slate-400">CEO, Luminance Creative</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Decorative blobs --}}
        <div class="absolute top-20 right-10 w-48 h-48 bg-primary/10 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="absolute bottom-20 left-10 w-64 h-64 bg-purple-300/20 rounded-full blur-[100px] pointer-events-none"></div>
    </div>

    {{-- ── Right form panel ── --}}
    <div class="flex-1 flex flex-col justify-center items-center px-6 md:px-12 py-12 bg-white">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-10 text-center">
            <div class="font-headline font-bold text-2xl text-slate-900">uMsizi<span class="text-primary">.</span></div>
            <p class="text-slate-400 text-sm mt-1">The Fluid Professional Workspace</p>
        </div>

        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>
    </div>

</div>

@livewireScripts
</body>
</html>
