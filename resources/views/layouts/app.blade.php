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
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-slate-200/80 bg-white/45 backdrop-blur-xl">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                <div class="mx-auto max-w-7xl px-4 pt-4" role="status">
                    @if (session('success'))<p class="rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>@endif
                    @if ($errors->any())<ul class="rounded-lg bg-rose-50 p-4 text-rose-800" role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                </div>
                {{ $slot }}
            </main>
        </div>

        <div id="permission-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-[28px] border border-white/10 bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 text-xl text-amber-600">!</div>
                    <div>
                        <p class="text-sm uppercase tracking-[0.18em] text-slate-500">Acesso negado</p>
                        <h3 class="text-xl font-bold tracking-[-0.04em] text-slate-900">Permissão necessária</h3>
                    </div>
                </div>
                <p id="permission-message" class="mt-5 text-base leading-7 text-slate-600">Você não tem permissão para realizar esta ação.</p>
                <div class="mt-6 flex justify-end">
                    <button type="button" onclick="document.getElementById('permission-modal').classList.add('hidden'); document.getElementById('permission-modal').classList.remove('flex');" class="apple-button">Entendi</button>
                </div>
            </div>
        </div>

        <script>
            window.showPermissionDenied = function (message) {
                const modal = document.getElementById('permission-modal');
                const text = document.getElementById('permission-message');
                text.textContent = message || 'Você não tem permissão para realizar esta ação.';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            document.addEventListener('click', function (event) {
                const element = event.target.closest('[data-permission]');
                if (!element) return;

                const hasPermission = element.dataset.permission === 'true';
                if (!hasPermission) {
                    event.preventDefault();
                    event.stopPropagation();
                    const message = element.dataset.permissionMessage || 'Você não tem permissão para realizar esta ação.';
                    window.showPermissionDenied(message);
                }
            });

            @if (session('permission_error'))
                document.addEventListener('DOMContentLoaded', function () {
                    window.showPermissionDenied(@js(session('permission_error')));
                });
            @endif
        </script>
    </body>
</html>
