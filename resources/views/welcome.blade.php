<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Ideias Corporativas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
                <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
                    <div class="text-xl font-bold">Ideias Corp</div>
                    <div class="flex gap-3">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ route('dashboard') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Entrar</a>
                                <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Cadastrar</a>
                            @endauth
                        @endif
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-6 py-16">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-indigo-600 font-semibold">Plataforma corporativa</p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">Transforme ideias em ganhos concretos.</h1>
                        <p class="mt-6 text-lg text-slate-600">Acompanhe propostas, avalie impacto, preserve anonimato e reconheça contribuições com critérios claros e justos.</p>
                        <div class="mt-8 flex flex-wrap gap-4">
                            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-5 py-3 text-base font-semibold text-white shadow hover:bg-indigo-500">Começar agora</a>
                            <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-base font-semibold text-slate-700">Fazer login</a>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                        <div class="space-y-4">
                            <div class="rounded-lg bg-slate-100 p-4">
                                <p class="text-sm text-slate-500">Ideias em avaliação</p>
                                <p class="mt-1 text-3xl font-bold text-slate-900">128</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-lg bg-emerald-50 p-4">
                                    <p class="text-sm text-emerald-700">Aprovadas</p>
                                    <p class="mt-1 text-2xl font-bold text-emerald-900">36</p>
                                </div>
                                <div class="rounded-lg bg-violet-50 p-4">
                                    <p class="text-sm text-violet-700">Votações abertas</p>
                                    <p class="mt-1 text-2xl font-bold text-violet-900">12</p>
                                </div>
                            </div>
                            <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                                Critérios de avaliação: impacto real, mérito técnico e participação dos colaboradores.
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
