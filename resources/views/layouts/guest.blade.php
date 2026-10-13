<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="apple-shell min-h-screen text-slate-900 antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="apple-card w-full max-w-6xl overflow-hidden">
                <div class="grid min-h-[760px] lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="hidden flex-col justify-between bg-[radial-gradient(circle_at_top_left,_rgba(0,113,227,0.18),transparent_30%),linear-gradient(135deg,#0b0b0d_0%,#131720_35%,#0f172a_100%)] p-10 text-white lg:flex">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-xl"></div>
                                <span class="text-lg font-semibold tracking-wide">Ideias Corp</span>
                            </div>
                            <a href="/" class="rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs uppercase tracking-[0.2em] text-white/80 hover:bg-white/10">Início</a>
                        </div>

                        <div class="space-y-6">
                            <span class="apple-pill border-white/15 bg-white/5 text-white/80">Inovação corporativa</span>
                            <h1 class="max-w-md text-5xl font-black tracking-[-0.06em] text-white">A forma mais elegante de transformar ideias em ação.</h1>
                            <p class="max-w-md text-base leading-7 text-slate-300">
                                Centralize propostas, priorize impacto e mantenha o processo de avaliação justo, transparente e seguro.
                            </p>
                        </div>

                        <div class="rounded-[28px] border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between text-xs uppercase tracking-[0.2em] text-slate-300">
                                <span>Fluxo</span>
                                <span>Digital</span>
                            </div>
                            <div class="mt-6 grid grid-cols-3 gap-3">
                                <div class="rounded-2xl bg-white/10 p-3 text-center">
                                    <div class="text-lg font-bold text-white">128</div>
                                    <div class="mt-1 text-[10px] uppercase tracking-[0.2em] text-slate-300">Ideias</div>
                                </div>
                                <div class="rounded-2xl bg-emerald-500/15 p-3 text-center">
                                    <div class="text-lg font-bold text-emerald-300">36</div>
                                    <div class="mt-1 text-[10px] uppercase tracking-[0.2em] text-emerald-200">Aprovadas</div>
                                </div>
                                <div class="rounded-2xl bg-violet-500/15 p-3 text-center">
                                    <div class="text-lg font-bold text-violet-300">12</div>
                                    <div class="mt-1 text-[10px] uppercase tracking-[0.2em] text-violet-200">Abertas</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center bg-white/60 p-6 sm:p-10 lg:p-12">
                        <div class="w-full max-w-md">
                            <div class="mb-8 flex items-center justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Acesso</p>
                                    <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-slate-900">Bem-vindo</h2>
                                </div>
                                <a href="/" class="text-sm font-medium text-slate-500 hover:text-slate-800">Voltar</a>
                            </div>

                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
