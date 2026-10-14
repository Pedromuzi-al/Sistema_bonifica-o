<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Conted Tech — Plataforma de Inovação</title>
        <link rel="icon" type="image/png" href="{{ asset('images/conted-tech-mark.png') }}">
        <script>
            if (window.localStorage.getItem('conted-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="apple-shell text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="site-header sticky top-0 z-50 border-b border-white/60 bg-white/35 backdrop-blur-2xl">
                <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <div class="brand-logo rounded-2xl border border-slate-200/80 bg-white px-4 py-2 shadow-sm shadow-slate-900/5">
                        <img src="{{ asset('images/conted-tech-logo-visible.png') }}" alt="Conted Tech — tecnologia educacional" class="h-auto w-60 object-contain sm:w-72">
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" data-theme-toggle aria-label="Ativar modo escuro" title="Ativar modo escuro" aria-pressed="false" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-sky-100">
                            <svg data-theme-icon="moon" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8 8.5 8.5 0 1 0 20.2 15.3Z" /></svg>
                            <svg data-theme-icon="sun" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" /></svg>
                            <span data-theme-label class="hidden sm:inline">Modo escuro</span>
                        </button>
                        @auth
                            <a href="{{ route('dashboard') }}" class="apple-button">Painel</a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="apple-button--light">Entrar</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="apple-button--primary">Cadastrar</a>
                            @endif
                        @endauth
                    </div>
                </nav>
            </header>

            <main class="mx-auto max-w-6xl px-6 pb-20 pt-12 lg:pt-20">
                <section class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="space-y-8 hero-animate">
                        <span class="apple-pill shimmer">Plataforma corporativa</span>
                        <div class="space-y-6 hero-animate-delay-1">
                            <h1 class="text-5xl font-black tracking-[-0.06em] text-slate-900 sm:text-6xl lg:text-7xl">
                                Transforme ideias em ganhos concretos.
                            </h1>
                            <p class="max-w-xl text-lg leading-8 text-slate-600">
                                Acompanhe propostas, avalie impacto, preserve anonimato e reconheça contribuições com critérios claros, justos e pensados para o crescimento da empresa.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-4 hero-animate-delay-2">
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="apple-button--primary">Começar agora</a>
                            @endif
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="apple-button--light">Fazer login</a>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-4 hero-animate-delay-3">
                            <div class="apple-card px-5 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Ideias em avaliação</p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">128</p>
                            </div>
                            <div class="apple-card px-5 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-emerald-600">Aprovadas</p>
                                <p class="mt-2 text-3xl font-bold text-emerald-700">36</p>
                            </div>
                            <div class="apple-card px-5 py-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-sky-600">Votações abertas</p>
                                <p class="mt-2 text-3xl font-bold text-sky-700">12</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative hero-animate-delay-2">
                        <div class="apple-card relative overflow-hidden p-4 sm:p-6">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),transparent_35%),radial-gradient(circle_at_bottom,_rgba(255,255,255,0.18),transparent_40%)]"></div>
                            <div class="relative rounded-[28px] bg-[#0b0b0d] p-4 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)]">
                                <div class="mb-4 flex items-center justify-between text-[10px] uppercase tracking-[0.2em] text-slate-400">
                                    <span class="text-sky-400">Conted Tech</span>
                                    <span>Ao vivo</span>
                                </div>
                                <div class="mb-8 flex items-center justify-between text-white/60">
                                    <span class="text-sm">Produtos</span>
                                    <span class="text-sm">Inovação</span>
                                </div>

                                <div class="relative rounded-[30px] bg-[#0c0d10] p-6">
                                    <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-white/5 to-transparent"></div>
                                    <div class="hero-device">
                                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.2),transparent_50%)]"></div>
                                    </div>
                                    <div class="apple-ambient"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
