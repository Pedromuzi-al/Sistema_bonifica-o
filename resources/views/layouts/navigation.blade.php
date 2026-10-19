@php
    $items = [
        ['route'=>'dashboard','match'=>'dashboard','label'=>'Painel','icon'=>'home','visible'=>true],
        ['route'=>'cycles.index','match'=>'cycles.*','label'=>'Votações','icon'=>'vote','visible'=>true],
        ['route'=>'ideas.index','match'=>'ideas.*','label'=>'Ideias','icon'=>'idea','visible'=>true],
        ['route'=>'notifications.index','match'=>'notifications.*','label'=>'Avisos','icon'=>'bell','visible'=>true],
        ['route'=>'evaluation.index','match'=>'evaluation.*','label'=>'Revisão de ideias','icon'=>'check','visible'=>auth()->user()->can('evaluate', new App\Models\Idea)],
        ['route'=>'admin.index','match'=>'admin.*','label'=>'Administração','icon'=>'settings','visible'=>auth()->user()->can('manage-admin')],
        ['route'=>'reports.index','match'=>'reports.*','label'=>'Relatórios','icon'=>'chart','visible'=>auth()->user()->can('manage-admin')],
    ];
@endphp
<button x-cloak x-show="mobile && sidebarOpen" @click="closeMenu()" type="button" class="sidebar-overlay" tabindex="-1" aria-label="Fechar menu lateral"></button>
<aside id="mobile-navigation" x-ref="sidebar" class="app-sidebar" :aria-hidden="mobile && !sidebarOpen ? 'true' : null" :inert="mobile && !sidebarOpen" @keydown="trapFocus($event)">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/conted-tech-logo-visible.png') }}" alt="Conted Tech — tecnologia educacional" class="brand-logo w-full rounded-lg bg-white p-2"></a>
        <button type="button" class="sidebar-close" @click="closeMenu()" aria-label="Fechar menu">×</button>
    </div>
    <nav aria-label="Menu principal" class="sidebar-links">
        <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Plataforma de ideias</p>
        @foreach($items as $item)
            @if($item['visible'])
                <a href="{{ route($item['route']) }}" class="app-nav-link sidebar-link" @if(($item['route'] === 'cycles.index' && request()->routeIs('ideas.vote-form')) || (request()->routeIs($item['match']) && !($item['route']==='admin.index' && request()->routeIs('admin.sectors.*','admin.users.*','admin.criteria.*','admin.teams.*','admin.organization')) && !($item['route'] === 'ideas.index' && request()->routeIs('ideas.vote-form')))) aria-current="page" @endif>
                    <x-sidebar-icon :name="$item['icon']" /><span>{{ $item['label'] }}</span>
                </a>
                @if($item['route'] === 'admin.index' && request()->routeIs('admin.*'))
                    <div class="ml-4 border-l border-slate-200 pl-2" aria-label="Áreas da administração">
                        @foreach([
                            ['route'=>'admin.sectors.index','match'=>'admin.sectors.*','label'=>'Setores'],
                            ['route'=>'admin.users.index','match'=>'admin.users.*','label'=>'Pessoas e acessos'],
                            ['route'=>'admin.criteria.index','match'=>'admin.criteria.*','label'=>'Critérios técnicos'],
                            ['route'=>'admin.organization','match'=>'admin.teams.*','label'=>'Equipes'],
                        ] as $section)
                            <a href="{{ route($section['route']) }}" class="app-nav-link sidebar-link" @if(request()->routeIs($section['match']) || ($section['route']==='admin.organization' && request()->routeIs('admin.organization'))) aria-current="page" @endif>{{ $section['label'] }}</a>
                        @endforeach
                    </div>
                @endif
            @endif
        @endforeach
    </nav>
    <div class="sidebar-footer space-y-2">
        <a href="{{ route('profile.edit') }}" class="app-nav-link sidebar-link" @if(request()->routeIs('profile.*')) aria-current="page" @endif><x-sidebar-icon name="user" /><span>Meu perfil</span></a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="app-nav-link sidebar-link w-full text-left"><x-sidebar-icon name="logout" /><span>Sair</span></button></form>
        <p class="px-3 pt-3 text-xs text-slate-500">Conted Tech · Ideias que fazem a diferença</p>
    </div>
</aside>
<div class="app-topbar site-header" :inert="mobile && sidebarOpen">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" x-ref="menuButton" @click="openMenu()" :aria-expanded="sidebarOpen.toString()" aria-controls="mobile-navigation" aria-label="Abrir menu" class="sidebar-toggle rounded-lg border border-slate-200 p-2 text-slate-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg></button>
        <span class="truncate text-sm font-medium text-slate-600">Plataforma de ideias</span>
    </div>
    <div class="flex shrink-0 items-center gap-3">
                <button type="button" data-theme-toggle aria-label="Ativar modo escuro" title="Ativar modo escuro" aria-pressed="false" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-sky-100">
                    <svg data-theme-icon="moon" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8 8.5 8.5 0 1 0 20.2 15.3Z" /></svg>
                    <svg data-theme-icon="sun" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" /></svg>
                    <span data-theme-label class="hidden sm:inline">Modo escuro</span>
                </button>

        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-full p-1 text-sm" aria-label="Meu perfil: {{ auth()->user()->name }}">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-500 font-bold text-slate-950">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span>
            <span class="hidden max-w-48 truncate text-slate-700 sm:block">{{ auth()->user()->name }}</span>
        </a>
    </div>
</div>
