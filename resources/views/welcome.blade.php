<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Ideias Corporativas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="apple-shell text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="sticky top-0 z-50 border-b border-white/60 bg-white/35 backdrop-blur-2xl">
                <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-lg font-bold text-white shadow-lg shadow-slate-900/15"></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Ideias Corp</p>
                            <p class="text-sm font-semibold text-slate-800">Plataforma</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
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
                                <p class="text-xs uppercase tracking-[0.18em] text-violet-600">Votações abertas</p>
                                <p class="mt-2 text-3xl font-bold text-violet-700">12</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative hero-animate-delay-2">
                        <div class="apple-card relative overflow-hidden p-4 sm:p-6">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),transparent_35%),radial-gradient(circle_at_bottom,_rgba(255,255,255,0.18),transparent_40%)]"></div>
                            <div class="relative rounded-[28px] bg-[#0b0b0d] p-4 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)]">
                                <div class="mb-4 flex items-center justify-between text-[10px] uppercase tracking-[0.2em] text-slate-400">
                                    <span>Ideias Corp</span>
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
