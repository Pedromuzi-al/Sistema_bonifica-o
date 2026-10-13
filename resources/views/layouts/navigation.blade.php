<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/55 backdrop-blur-2xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-20 items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-lg font-bold text-white"></div>
                    <div>
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Ideias Corp</div>
                        <div class="text-sm font-semibold text-slate-800">Workspace</div>
                    </div>
                </a>
            </div>

            <div class="hidden items-center gap-2 sm:flex">
                <a href="{{ route('dashboard') }}" class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : '' }}">Painel</a>
                <a href="{{ route('ideas.index') }}" class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('ideas.*') ? 'bg-slate-900 text-white' : '' }}">Ideias</a>
                @php($canEvaluate = auth()->user()->can('evaluate', new App\Models\Idea))
                @if ($canEvaluate)
                    <a href="{{ route('evaluation.index') }}" data-permission="true" data-permission-message="Você não tem permissão para acessar a avaliação." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('evaluation.*') ? 'bg-slate-900 text-white' : '' }}">Avaliação</a>
                @else
                    <button type="button" data-permission="false" data-permission-message="Você não tem permissão para acessar a avaliação." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Avaliação</button>
                @endif
                @php($canAccessAdmin = auth()->user()->can('manage-admin'))
                @if ($canAccessAdmin)
                    <a href="{{ route('admin.index') }}" data-permission="true" data-permission-message="Você não tem permissão para acessar o painel administrativo." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('admin.*') ? 'bg-slate-900 text-white' : '' }}">Administração</a>
                @else
                    <button type="button" data-permission="false" data-permission-message="Você não tem permissão para acessar o painel administrativo." class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Administração</button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-full border border-slate-200 bg-white/70 px-2 py-1.5 sm:flex hover:bg-slate-50 transition-colors">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 via-indigo-500 to-violet-500 text-sm font-bold text-white">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    <div class="pr-2 text-sm font-medium text-slate-700">{{ Auth::user()->name }}</div>
                </a>

                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/80 p-2 text-slate-600 shadow-sm hover:bg-slate-50 sm:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hidden rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 sm:inline-flex">Sair</button>
                </form>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200/80 bg-white/80 sm:hidden">
        <div class="mx-auto max-w-7xl space-y-2 px-4 py-4">
            <a href="{{ route('dashboard') }}" class="block rounded-2xl px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : '' }}">Painel</a>
            <a href="{{ route('ideas.index') }}" class="block rounded-2xl px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 {{ request()->routeIs('ideas.*') ? 'bg-slate-900 text-white' : '' }}">Ideias</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full rounded-2xl border border-slate-200 px-3 py-3 text-left text-sm font-medium text-slate-700 hover:bg-slate-100">Sair</button>
            </form>
        </div>
    </div>
</nav>
