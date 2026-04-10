<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>uMsizi | The Fluid Professional Workspace</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4e45e4',
                        'primary-dim': '#4135d8',
                        'primary-light': '#edeaff',
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
            font-style: normal;
            font-weight: normal;
            display: inline-block;
            line-height: 1;
        }
        .hero-bg {
            background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(78,69,228,0.12) 0%, transparent 70%),
                        radial-gradient(ellipse 50% 40% at 90% 80%, rgba(116,84,121,0.08) 0%, transparent 60%),
                        #fafafa;
        }
        .watermark {
            font-family: 'Manrope', sans-serif;
            font-weight: 900;
            font-size: clamp(80px, 18vw, 200px);
            line-height: 0.85;
            color: transparent;
            -webkit-text-stroke: 1.5px rgba(78,69,228,0.08);
            user-select: none;
            pointer-events: none;
            letter-spacing: -0.02em;
        }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(78,69,228,0.1); }
        .logo-strip { mask-image: linear-gradient(to right, transparent, black 20%, black 80%, transparent); }
    </style>
</head>
<body class="bg-[#fafafa] text-slate-800 overflow-x-hidden">

{{-- ═══════════════════════════════════════════════════════
     NAV
════════════════════════════════════════════════════════ --}}
<nav class="fixed top-0 left-0 right-0 z-50 h-16 px-6 md:px-12 flex items-center justify-between bg-white/80 backdrop-blur-xl border-b border-slate-100">
    <a href="/" class="font-headline font-bold text-lg text-slate-900 tracking-tight">uMsizi<span class="text-primary">.</span></a>

    <div class="hidden md:flex items-center gap-8">
        <a href="#features" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Features</a>
        <a href="#workflow" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Workflow</a>
        <a href="#testimonial" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Testimonials</a>
        <a href="#pricing" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Pricing</a>
    </div>

    <div class="flex items-center gap-3">
        @if(Route::has('login'))
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-primary transition-colors">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="bg-primary text-white text-sm font-semibold px-5 py-2 rounded-lg hover:bg-primary-dim transition-colors shadow-sm shadow-primary/20">Sign In</a>
            @endauth
        @endif
    </div>
</nav>

{{-- ═══════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════ --}}
<section class="hero-bg pt-32 pb-16 px-6 text-center relative overflow-hidden">

    {{-- Badge --}}
    <div class="inline-flex items-center gap-2 bg-primary/10 text-primary text-xs font-semibold px-4 py-1.5 rounded-full mb-8 border border-primary/20">
        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
        Now with AI-powered HR insights
    </div>

    {{-- Headline --}}
    <h1 class="font-headline font-black text-5xl md:text-7xl leading-[1.05] tracking-tight text-slate-900 max-w-4xl mx-auto mb-6">
        Real-time<br/>
        <span class="text-primary">performance</span> management
    </h1>

    <p class="text-slate-500 text-base md:text-lg max-w-xl mx-auto mb-10 leading-relaxed">
        From financial metrics to payroll and leave management, our dashboard provides a holistic view of your organisation's operations.
    </p>

    {{-- Email CTA --}}
    <form class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto mb-16">
        <div class="relative flex-1 w-full">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">mail</span>
            <input type="email" placeholder="Your email address"
                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 shadow-sm"/>
        </div>
        @auth
        <a href="{{ route('dashboard') }}" class="bg-primary text-white text-sm font-semibold px-6 py-3 rounded-xl hover:bg-primary-dim transition-colors whitespace-nowrap shadow-sm shadow-primary/20">
            Go to Dashboard
        </a>
        @else
        <a href="{{ route('login') }}" class="bg-primary text-white text-sm font-semibold px-6 py-3 rounded-xl hover:bg-primary-dim transition-colors whitespace-nowrap shadow-sm shadow-primary/20">
            Sign In
        </a>
        @endauth
    </form>

    {{-- Logo strip --}}
    <div class="logo-strip max-w-3xl mx-auto mb-16">
        <p class="text-xs text-slate-400 uppercase tracking-widest font-medium mb-5">Trusted by top-tier product companies</p>
        <div class="flex items-center justify-center gap-10 md:gap-16 flex-wrap opacity-50 grayscale">
            @foreach(['SnapShot','Sitemark','Blooom','hues','Volume','Cactus'] as $brand)
            <span class="font-headline font-bold text-slate-600 text-sm tracking-tight">{{ $brand }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     DASHBOARD MOCKUP + WATERMARK
════════════════════════════════════════════════════════ --}}
<section class="relative overflow-hidden py-4 px-6">
    {{-- Watermark --}}
    <div class="absolute inset-0 flex flex-col items-center justify-center select-none pointer-events-none z-0 gap-0 leading-none overflow-hidden">
        <div class="watermark">uMsizi</div>
        <div class="watermark">Dashboard</div>
    </div>

    {{-- Mockup card --}}
    <div class="relative z-10 max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200 border border-slate-100 overflow-hidden">
            {{-- Fake browser chrome --}}
            <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 border-b border-slate-100">
                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                <div class="flex-1 mx-4 h-6 bg-slate-200 rounded-md text-[10px] text-slate-400 flex items-center px-3">app.umsizi.co/dashboard</div>
            </div>
            {{-- Fake dashboard UI --}}
            <div class="flex" style="min-height:320px;">
                {{-- Sidebar --}}
                <div class="w-44 bg-slate-50 border-r border-slate-100 p-4 flex flex-col gap-1 flex-shrink-0">
                    <div class="font-headline font-bold text-xs text-primary mb-3 px-2">uMsizi HR</div>
                    @foreach(['Dashboard','Employees','Leave','Payroll','Tasks','Folders','Reports'] as $item)
                    <div class="px-2 py-1.5 rounded-lg text-[11px] {{ $item === 'Dashboard' ? 'bg-primary/10 text-primary font-semibold' : 'text-slate-500' }}">{{ $item }}</div>
                    @endforeach
                </div>
                {{-- Main content --}}
                <div class="flex-1 p-5 bg-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-headline font-bold text-sm text-slate-800">Good morning, Alex 👋</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Here's what's happening today</div>
                        </div>
                        <div class="flex gap-2">
                            <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center"><span class="material-symbols-outlined text-primary" style="font-size:14px;">notifications</span></div>
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-purple-500 flex items-center justify-center text-white text-[10px] font-bold">AK</div>
                        </div>
                    </div>
                    {{-- Stat cards --}}
                    <div class="grid grid-cols-4 gap-3 mb-4">
                        @foreach([['Employees','142','groups','bg-blue-50 text-blue-600'],['On Leave','8','beach_access','bg-amber-50 text-amber-600'],['Open Tasks','23','checklist','bg-purple-50 text-purple-600'],['Payroll','$48k','payments','bg-green-50 text-green-600']] as [$label,$val,$icon,$color])
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                            <div class="w-7 h-7 rounded-lg {{ $color }} flex items-center justify-center mb-2">
                                <span class="material-symbols-outlined" style="font-size:14px;">{{ $icon }}</span>
                            </div>
                            <div class="font-headline font-bold text-sm text-slate-800">{{ $val }}</div>
                            <div class="text-[10px] text-slate-400">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>
                    {{-- Chart placeholder --}}
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <div class="text-[10px] font-semibold text-slate-500 mb-2">Headcount trend</div>
                        <div class="flex items-end gap-1 h-14">
                            @foreach([40,55,45,70,60,80,65,90,75,95,85,100] as $h)
                            <div class="flex-1 rounded-sm" style="height:{{ $h }}%;background:linear-gradient(to top,#4e45e4,#8582ff);opacity:{{ 0.4 + $h/200 }}"></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     FEATURES
════════════════════════════════════════════════════════ --}}
<section id="features" class="py-24 px-6 md:px-12 bg-white">
    <div class="max-w-6xl mx-auto">

        <div class="mb-14">
            <h2 class="font-headline font-black text-4xl md:text-5xl text-slate-900 leading-tight mb-4">
                Get your money's <span class="text-primary">worth</span>
            </h2>
            <p class="text-slate-500 max-w-lg text-base leading-relaxed">
                From financial metrics to payroll and leave data, our dashboard provides a holistic view of your business operations.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Feature 1 --}}
            <div class="card-hover bg-slate-50 rounded-2xl p-7 border border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-5">
                    <span class="material-symbols-outlined text-[20px]">edit_note</span>
                </div>
                <h3 class="font-headline font-bold text-lg text-slate-900 mb-2">Employee Record Management</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-5">Create, edit, and manage employee records with full lifecycle tracking from onboarding to offboarding.</p>
                {{-- Mini UI mockup --}}
                <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary text-xs font-bold">JD</div>
                        <div>
                            <div class="h-2.5 w-24 bg-slate-200 rounded-full"></div>
                            <div class="h-2 w-16 bg-slate-100 rounded-full mt-1"></div>
                        </div>
                        <span class="ml-auto text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Active</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['Department','Position','Hire Date','Salary'] as $f)
                        <div class="bg-slate-50 rounded-lg p-2">
                            <div class="text-[9px] text-slate-400 mb-1">{{ $f }}</div>
                            <div class="h-2 bg-slate-200 rounded-full w-3/4"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Feature 2 --}}
            <div class="card-hover bg-slate-50 rounded-2xl p-7 border border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600 mb-5">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                </div>
                <h3 class="font-headline font-bold text-lg text-slate-900 mb-2">Team Collaboration</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-5">Tasks can be assigned, tracked, and categorised by project, tag, or theme for better team organisation.</p>
                {{-- Avatar stack --}}
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex -space-x-2">
                        @foreach(['bg-primary/20 text-primary','bg-purple-200 text-purple-700','bg-amber-200 text-amber-700','bg-green-200 text-green-700'] as $i => $c)
                        <div class="w-9 h-9 rounded-full border-2 border-white {{ $c }} flex items-center justify-center text-xs font-bold">{{ ['AK','SR','MT','JL'][$i] }}</div>
                        @endforeach
                        <div class="w-9 h-9 rounded-full border-2 border-white bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-500">+2</div>
                    </div>
                    <span class="text-xs text-slate-400">5 team members</span>
                </div>
                {{-- Progress tracking mockup --}}
                <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Live</span>
                        <span class="material-symbols-outlined text-slate-300 text-[16px]">more_horiz</span>
                    </div>
                    <div class="font-headline font-bold text-sm text-slate-800 mb-1">Q2 Performance Review</div>
                    <div class="text-[11px] text-slate-400 mb-3">Brings team members' diverse experience into play</div>
                    <div class="flex items-center gap-4 text-[10px] text-slate-400">
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">chat_bubble</span> 12 comments</span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">attach_file</span> 9 files</span>
                    </div>
                </div>
            </div>

            {{-- Feature 3 --}}
            <div class="card-hover bg-slate-50 rounded-2xl p-7 border border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 mb-5">
                    <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                </div>
                <h3 class="font-headline font-bold text-lg text-slate-900 mb-2">Payroll & Accounts</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-5">Run payroll, manage procurement requests, overtime claims, and transport allowances in one place.</p>
                {{-- Salary range mockup --}}
                <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm space-y-3">
                    <div class="text-[10px] text-slate-400 font-medium">Salary range</div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['$10–20','$20–40','$40–80','+$80'] as $range)
                        <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-[11px] font-medium text-slate-600">{{ $range }}</div>
                        @endforeach
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-2">Skills</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(['Engineering','Architecture','3D Printing Expert'] as $skill)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-primary/10 text-primary font-medium">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Feature 4 --}}
            <div class="card-hover bg-slate-50 rounded-2xl p-7 border border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center text-green-600 mb-5">
                    <span class="material-symbols-outlined text-[20px]">analytics</span>
                </div>
                <h3 class="font-headline font-bold text-lg text-slate-900 mb-2">Data Export & Integration</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-5">Export HR data or integrate the dashboard with productivity tools and calendars seamlessly.</p>
                {{-- Mini chart --}}
                <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-semibold text-slate-600">Today's Sales</span>
                        <span class="text-[10px] text-green-600 font-semibold">↑ 12.4%</span>
                    </div>
                    <div class="flex items-end gap-1 h-16">
                        @foreach([30,50,40,70,55,80,60,90,70,85,75,95] as $h)
                        <div class="flex-1 rounded-sm bg-gradient-to-t from-primary/60 to-primary/20" style="height:{{ $h }}%"></div>
                        @endforeach
                    </div>
                    <div class="flex justify-between mt-2">
                        @foreach(['Jan','Feb','Mar','Apr','May','Jun'] as $m)
                        <span class="text-[9px] text-slate-300">{{ $m }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     EXTRA FEATURES ROW
════════════════════════════════════════════════════════ --}}
<section class="py-16 px-6 md:px-12 bg-slate-50 border-t border-slate-100">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach([
            ['calendar_today','Leave Management','Apply for leave, track balances, and manage approvals with automated workflows and real-time status updates.'],
            ['inventory_2','Asset Tracking','Assign and track company assets to employees. Full audit trail from assignment to return.'],
            ['folder_shared','Secure Folders','Create encrypted shared folders, control access per user, and collaborate on documents and spreadsheets.'],
        ] as [$icon,$title,$desc])
        <div class="card-hover bg-white rounded-2xl p-7 border border-slate-100 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-5">
                <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
            </div>
            <h3 class="font-headline font-bold text-base text-slate-900 mb-2">{{ $title }}</h3>
            <p class="text-slate-500 text-sm leading-relaxed">{{ $desc }}</p>
        </div>
        @endforeach
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     TESTIMONIAL
════════════════════════════════════════════════════════ --}}
<section id="testimonial" class="py-24 px-6 bg-white">
    <div class="max-w-3xl mx-auto text-center">
        <div class="inline-flex items-center gap-1 mb-8">
            @for($i=0;$i<5;$i++)
            <span class="material-symbols-outlined text-amber-400 text-[20px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">star</span>
            @endfor
        </div>
        <blockquote class="font-headline font-bold text-2xl md:text-3xl text-slate-900 leading-snug mb-8">
            "uMsizi has completely transformed how we view human resources. It's no longer a back-office burden — it's a strategic advantage our employees actually enjoy using."
        </blockquote>
        <div class="flex flex-col items-center gap-2">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-purple-500 flex items-center justify-center text-white font-bold">ER</div>
            <div class="font-headline font-semibold text-slate-900 text-sm">Elena Rodriguez</div>
            <div class="text-slate-400 text-xs">CEO at Luminance Creative</div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     CTA
════════════════════════════════════════════════════════ --}}
<section id="workflow" class="py-20 px-6">
    <div class="max-w-5xl mx-auto bg-primary rounded-3xl p-12 md:p-16 text-center relative overflow-hidden shadow-2xl shadow-primary/30">
        <div class="absolute inset-0 bg-gradient-to-br from-primary to-[#4135d8] opacity-60 pointer-events-none"></div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
        <div class="relative z-10">
            <h2 class="font-headline font-black text-4xl md:text-5xl text-white mb-4 leading-tight">Ready to find your flow?</h2>
            <p class="text-white/70 text-base max-w-md mx-auto mb-10">Join forward-thinking teams who have upgraded their workplace experience.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                @auth
                <a href="{{ route('dashboard') }}" class="bg-white text-primary font-bold px-8 py-3.5 rounded-xl hover:bg-slate-50 transition-colors text-sm shadow-sm">Go to Dashboard</a>
                @else
                <a href="{{ route('login') }}" class="bg-white text-primary font-bold px-8 py-3.5 rounded-xl hover:bg-slate-50 transition-colors text-sm shadow-sm">Sign In</a>
                @endauth
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════ --}}
<footer class="bg-white border-t border-slate-100 px-6 md:px-12 py-10">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
        <div>
            <div class="font-headline font-bold text-slate-900">uMsizi<span class="text-primary">.</span></div>
            <p class="text-xs text-slate-400 mt-1">© {{ date('Y') }} uMsizi. The Fluid Professional Workspace.</p>
        </div>
        <div class="flex gap-8">
            @foreach(['Privacy Policy','Terms of Service','Security','Status'] as $link)
            <a href="#" class="text-xs text-slate-400 hover:text-primary transition-colors">{{ $link }}</a>
            @endforeach
        </div>
        <a href="https://henrix.co.zw" target="_blank" rel="noopener noreferrer"
            class="text-xs text-slate-400 hover:text-primary transition-colors flex items-center gap-1">
            ⚡ Powered by Henrix
        </a>
    </div>
</footer>

</body>
</html>
