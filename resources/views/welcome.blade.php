<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>uMsizi | The Fluid Professional Workspace</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

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
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .glass-card { background: rgba(255,255,255,0.4); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.2); }
    </style>
</head>
<body class="bg-background text-on-surface overflow-x-hidden selection:bg-primary-container">

    <!-- Nav -->
    <nav class="fixed top-0 left-0 right-0 h-20 px-8 md:px-16 flex justify-between items-center z-50 bg-white/60 backdrop-blur-2xl">
        <div class="text-xl font-bold tracking-tight text-indigo-700 font-headline">uMsizi</div>
        <div class="hidden md:flex gap-8 items-center">
            <a href="#features" class="text-sm font-medium text-slate-500 hover:text-indigo-500 transition-colors">Features</a>
            <a href="#workflow" class="text-sm font-medium text-slate-500 hover:text-indigo-500 transition-colors">Workflow</a>
            <a href="#testimonial" class="text-sm font-medium text-slate-500 hover:text-indigo-500 transition-colors">About</a>
        </div>
        <div class="flex items-center gap-4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:opacity-80 transition-opacity">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-500 hover:text-indigo-500 transition-colors">Login</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-6 py-2.5 rounded-xl font-semibold text-sm shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity">Sign Up</a>
                    @endif
                @endauth
            @endif
        </div>
    </nav>

    <main class="relative pt-20">

        <!-- Hero -->
        <section class="relative min-h-[90vh] flex flex-col items-center justify-center px-6 overflow-hidden">
            <div class="absolute inset-0 -z-10 overflow-hidden">
                <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[60%] rounded-full bg-primary/10 blur-[120px]"></div>
                <div class="absolute bottom-[-10%] right-[-5%] w-[40%] h-[50%] rounded-full bg-tertiary/10 blur-[100px]"></div>
            </div>

            <div class="max-w-5xl w-full mx-auto text-center z-10">
                <div class="glass-card rounded-[2.5rem] p-12 md:p-20 shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10">
                        <span class="material-symbols-outlined text-[120px]">bubble_chart</span>
                    </div>
                    <span class="inline-block px-4 py-1.5 rounded-full bg-tertiary-container text-on-tertiary-container text-xs font-bold tracking-widest uppercase mb-6 font-label">The Future of Work</span>
                    <h1 class="font-headline text-5xl md:text-7xl font-extrabold tracking-tight text-on-surface leading-[1.1] mb-8">
                        The Fluid <span class="text-primary italic">Professional</span> Workspace.
                    </h1>
                    <p class="font-body text-lg md:text-xl text-on-surface-variant max-w-2xl mx-auto mb-12">
                        Human resource management, re-imagined for the modern era. Experience an interface that flows with your team, reducing noise and amplifying culture.
                    </p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-10 py-4 rounded-xl font-bold text-lg shadow-xl shadow-primary/25 hover:scale-[1.02] transition-transform">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-10 py-4 rounded-xl font-bold text-lg shadow-xl shadow-primary/25 hover:scale-[1.02] transition-transform">
                                Get Started Free
                            </a>
                            <a href="{{ route('login') }}" class="bg-surface-container-highest text-on-surface px-10 py-4 rounded-xl font-bold text-lg hover:bg-surface-container-high transition-colors">
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

            <div class="mt-20 w-full max-w-4xl mx-auto px-6">
                <p class="text-center font-label text-sm text-outline mb-8 tracking-widest uppercase">Trusted by forward-thinking teams</p>
                <div class="glass-card rounded-2xl py-8 px-12 flex flex-wrap justify-center items-center gap-12 md:gap-20 opacity-70 grayscale">
                    <div class="h-8 w-24 bg-on-surface-variant/20 rounded-md"></div>
                    <div class="h-8 w-32 bg-on-surface-variant/20 rounded-md"></div>
                    <div class="h-8 w-28 bg-on-surface-variant/20 rounded-md"></div>
                    <div class="h-8 w-20 bg-on-surface-variant/20 rounded-md"></div>
                </div>
            </div>
        </section>

        <!-- Features Bento Grid -->
        <section id="features" class="py-24 px-8 md:px-16 bg-surface-container-low">
            <div class="max-w-7xl mx-auto">
                <div class="mb-16 text-center max-w-3xl mx-auto">
                    <h2 class="font-headline text-4xl font-bold text-on-surface mb-4">Precision tools for human growth</h2>
                    <p class="font-body text-on-surface-variant">We've stripped away the enterprise clutter to leave you with what actually matters: your people and their potential.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <!-- Smart Directory -->
                    <div class="md:col-span-8 bg-surface-container-lowest rounded-3xl p-10 flex flex-col justify-between group overflow-hidden relative min-h-[320px]">
                        <div class="z-10">
                            <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                                <span class="material-symbols-outlined text-3xl">account_tree</span>
                            </div>
                            <h3 class="font-headline text-2xl font-bold mb-4">Smart Directory</h3>
                            <p class="text-on-surface-variant max-w-md">Navigate your organization through an intelligent visual graph. Understand relationships, skill sets, and team dynamics at a glance.</p>
                        </div>
                        <div class="mt-12 flex -space-x-4">
                            <div class="w-16 h-16 rounded-full border-4 border-surface-container-lowest bg-primary/20 flex items-center justify-center text-primary font-bold">JD</div>
                            <div class="w-16 h-16 rounded-full border-4 border-surface-container-lowest bg-tertiary/20 flex items-center justify-center text-tertiary font-bold">SR</div>
                            <div class="w-16 h-16 rounded-full border-4 border-surface-container-lowest bg-secondary/20 flex items-center justify-center text-secondary font-bold">MT</div>
                            <div class="w-16 h-16 rounded-full border-4 border-surface-container-lowest bg-primary-container flex items-center justify-center text-on-primary-container font-bold">+12</div>
                        </div>
                        <div class="absolute right-[-10%] bottom-[-10%] opacity-5 group-hover:scale-110 transition-transform duration-700">
                            <span class="material-symbols-outlined text-[300px]">diversity_3</span>
                        </div>
                    </div>

                    <!-- Pulse Check -->
                    <div class="md:col-span-4 bg-gradient-to-br from-tertiary to-tertiary-dim text-white rounded-3xl p-10 flex flex-col justify-between overflow-hidden relative min-h-[320px]">
                        <div class="z-10">
                            <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white mb-6">
                                <span class="material-symbols-outlined text-3xl">analytics</span>
                            </div>
                            <h3 class="font-headline text-2xl font-bold mb-4 text-on-tertiary">Pulse Check Metrics</h3>
                            <p class="text-tertiary-fixed-dim">Real-time sentiment and productivity heatmaps to help you prevent burnout before it happens.</p>
                        </div>
                        <div class="mt-8">
                            <div class="w-full h-32 flex items-end gap-2">
                                <div class="flex-1 bg-white/20 rounded-t-lg h-[40%]"></div>
                                <div class="flex-1 bg-white/40 rounded-t-lg h-[60%]"></div>
                                <div class="flex-1 bg-white/60 rounded-t-lg h-[90%]"></div>
                                <div class="flex-1 bg-white/30 rounded-t-lg h-[50%]"></div>
                                <div class="flex-1 bg-white/80 rounded-t-lg h-[75%]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Unified Workflow -->
                    <div class="md:col-span-12 bg-surface-container-lowest rounded-3xl p-10 flex flex-col md:flex-row items-center gap-12 overflow-hidden">
                        <div class="flex-1">
                            <div class="w-12 h-12 rounded-2xl bg-secondary-container flex items-center justify-center text-secondary mb-6">
                                <span class="material-symbols-outlined text-3xl">hub</span>
                            </div>
                            <h3 class="font-headline text-3xl font-bold mb-4">Unified Workflow</h3>
                            <p class="text-on-surface-variant text-lg">Payroll, benefits, performance reviews, and onboarding. One fluid interface to manage the entire employee lifecycle without ever switching tabs.</p>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <span class="px-4 py-2 rounded-full bg-surface-container text-on-surface-variant text-sm font-medium">Auto-Payroll</span>
                                <span class="px-4 py-2 rounded-full bg-surface-container text-on-surface-variant text-sm font-medium">1-Click Onboarding</span>
                                <span class="px-4 py-2 rounded-full bg-surface-container text-on-surface-variant text-sm font-medium">Compliance Vault</span>
                            </div>
                        </div>
                        <div class="flex-1 relative min-h-[180px] w-full">
                            <div class="glass-card rounded-2xl p-6 shadow-xl w-full rotate-2">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-10 h-10 rounded-full bg-primary/20"></div>
                                    <div class="h-4 w-32 bg-surface-variant rounded-full"></div>
                                </div>
                                <div class="space-y-3">
                                    <div class="h-3 w-full bg-surface-container rounded-full"></div>
                                    <div class="h-3 w-5/6 bg-surface-container rounded-full"></div>
                                    <div class="h-3 w-4/6 bg-surface-container rounded-full"></div>
                                </div>
                            </div>
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 glass-card rounded-2xl p-4 shadow-2xl -rotate-3 w-4/5">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-xs font-bold text-primary tracking-widest">TASK COMPLETE</span>
                                    <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                                </div>
                                <div class="h-2 w-full bg-primary/20 rounded-full">
                                    <div class="h-full w-full bg-primary rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonial -->
        <section id="testimonial" class="py-24 relative px-8 overflow-hidden">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full -z-10 opacity-30">
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-primary/20 rounded-full blur-[100px]"></div>
            </div>
            <div class="max-w-4xl mx-auto text-center">
                <div class="glass-card p-12 md:p-16 rounded-[3rem] shadow-xl">
                    <span class="material-symbols-outlined text-primary text-6xl mb-8 block" style="font-variation-settings:'FILL' 1">format_quote</span>
                    <h3 class="font-headline text-2xl md:text-3xl font-bold text-on-surface mb-8 leading-relaxed">
                        "uMsizi has completely transformed how we view human resources. It's no longer a back-office burden, but a strategic advantage that our employees actually enjoy using."
                    </h3>
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold text-xl mb-4 border-2 border-primary">ER</div>
                        <div class="font-headline font-bold text-on-surface">Elena Rodriguez</div>
                        <div class="font-body text-sm text-on-surface-variant">CEO at Luminance Creative</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="workflow" class="py-20 px-8">
            <div class="max-w-5xl mx-auto bg-primary text-on-primary rounded-[2.5rem] p-12 md:p-20 text-center relative overflow-hidden shadow-2xl shadow-primary/30">
                <div class="absolute inset-0 bg-gradient-to-br from-primary to-primary-dim opacity-50"></div>
                <div class="relative z-10">
                    <h2 class="font-headline text-4xl md:text-5xl font-extrabold mb-6">Ready to find your flow?</h2>
                    <p class="text-on-primary-fixed-variant text-lg max-w-xl mx-auto mb-10">
                        Join forward-thinking teams who have upgraded their workplace experience. Get started today.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="bg-surface-container-lowest text-primary px-8 py-4 rounded-xl font-bold text-lg hover:bg-on-primary-fixed-variant transition-colors">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="bg-surface-container-lowest text-primary px-8 py-4 rounded-xl font-bold text-lg hover:bg-on-primary-fixed-variant transition-colors">
                                Get Started Now
                            </a>
                            <a href="{{ route('login') }}" class="border border-white/30 text-white px-8 py-4 rounded-xl font-bold text-lg backdrop-blur-sm hover:bg-white/10 transition-colors">
                                Sign In
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-slate-50 w-full px-8 md:px-16 flex flex-col md:flex-row justify-between items-center gap-6 py-12 border-t border-slate-200/50">
        <div class="flex flex-col gap-2">
            <div class="text-lg font-bold text-indigo-700 font-headline">uMsizi</div>
            <p class="text-sm text-slate-500">© {{ date('Y') }} uMsizi. The Fluid Professional Workspace.</p>
        </div>
        <div class="flex flex-wrap justify-center gap-8">
            <a href="#" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">Privacy Policy</a>
            <a href="#" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">Terms of Service</a>
            <a href="#" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">Security</a>
            <a href="#" class="text-sm text-slate-500 hover:text-indigo-500 transition-colors">Status</a>
        </div>
    </footer>

</body>
</html>
