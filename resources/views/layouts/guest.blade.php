<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Conted Tech — Plataforma de Inovação</title>
        <link rel="icon" type="image/png" href="{{ asset('images/conted-tech-mark.png') }}">
        <script>
            if (window.localStorage.getItem('conted-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="apple-shell min-h-screen text-slate-900 antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="apple-card w-full max-w-6xl overflow-hidden">
                <div class="grid min-h-[760px] lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="guest-brand-panel hidden flex-col justify-between bg-[radial-gradient(circle_at_top_left,_rgba(15,173,223,0.22),transparent_34%),linear-gradient(135deg,#f6f7f8_0%,#ffffff_55%,#e9f6fa_100%)] p-10 text-slate-800 lg:flex">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('images/conted-tech-logo-visible.png') }}" alt="Conted Tech — tecnologia educacional" class="brand-logo h-auto w-72 max-w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 object-contain shadow-xs shadow-slate-900/5">
                            </div>
                            <a href="/" class="rounded-full border border-slate-300 bg-white/70 px-3 py-1.5 text-xs uppercase tracking-[0.2em] text-slate-600 hover:bg-white">Início</a>
                        </div>

                        <div class="stack-6">
                            <span class="apple-pill border-sky-200 bg-white/70 text-sky-700">Inovação corporativa</span>
                            <h1 class="max-w-md text-5xl font-black tracking-[-0.06em] text-slate-900">A forma mais elegante de transformar ideias em ação.</h1>
                            <p class="max-w-md text-base leading-7 text-slate-600">
                                Centralize propostas, priorize impacto e mantenha o processo de avaliação justo, transparente e seguro.
                            </p>
                        </div>

                        <div class="rounded-[28px] border border-slate-200 bg-white/65 p-4 shadow-xs backdrop-blur-xs">
                            <div class="flex items-center justify-between text-xs uppercase tracking-[0.2em] text-slate-500">
                                <span>Fluxo</span>
                                <span>Digital</span>
                            </div>
                            <div class="mt-6 grid grid-cols-3 gap-3">
                                <div class="rounded-2xl bg-slate-100 p-3 text-center">
                                    <div class="text-lg font-bold text-slate-900">128</div>
                                    <div class="mt-1 text-[10px] uppercase tracking-[0.2em] text-slate-500">Ideias</div>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 p-3 text-center">
                                    <div class="text-lg font-bold text-emerald-700">36</div>
                                    <div class="mt-1 text-[10px] uppercase tracking-[0.2em] text-emerald-600">Aprovadas</div>
                                </div>
                                <div class="rounded-2xl bg-sky-100 p-3 text-center">
                                    <div class="text-lg font-bold text-sky-700">12</div>
                                    <div class="mt-1 text-[10px] uppercase tracking-[0.2em] text-sky-700">Abertas</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="guest-form-panel flex items-center justify-center bg-white/60 p-6 sm:p-10 lg:p-12">
                        <div class="w-full max-w-md">
                            <div class="mb-8 flex items-center justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Acesso</p>
                                    <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-slate-900">Bem-vindo</h2>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button type="button" data-theme-toggle aria-label="Ativar modo escuro" title="Ativar modo escuro" aria-pressed="false" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 focus:outline-hidden focus:ring-4 focus:ring-sky-100">
                                        <svg data-theme-icon="moon" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8 8.5 8.5 0 1 0 20.2 15.3Z" /></svg>
                                        <svg data-theme-icon="sun" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" /></svg>
                                        <span data-theme-label class="hidden sm:inline">Modo escuro</span>
                                    </button>
                                    <a href="/" class="text-sm font-medium text-slate-500 hover:text-slate-800">Voltar</a>
                                </div>
                            </div>

                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
