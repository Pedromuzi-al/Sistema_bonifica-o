<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Visão geral</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-slate-900">Painel corporativo</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl stack-6 px-4 sm:px-6 lg:px-8">
            @unless(auth()->user()->canViewPublicIdeas())<section class="apple-card stack-2 p-5"><h3 class="font-bold">Sua conta aguarda liberação</h3><p>Peça ao administrador para liberar seu acesso. Depois disso, você poderá enviar ideias e participar das votações.</p></section>@endunless
            <section class="apple-card dashboard-welcome p-6 sm:p-8">
                <div class="max-w-3xl stack-4">
                    <p class="welcome-eyebrow">Olá, {{ Str::before(auth()->user()->name, ' ') }}</p>
                    <h3 class="text-2xl font-bold tracking-tight sm:text-3xl">Boas ideias merecem sair do papel.</h3>
                    <p class="welcome-description">Registre uma oportunidade, participe das votações e acompanhe as contribuições da equipe.</p>
                    <div class="stack-3 pt-2"><p class="text-sm font-semibold">Por onde começar?</p><div class="flex flex-wrap gap-3">@can('create', App\Models\Idea::class)<a class="apple-button" href="{{ route('ideas.create') }}">Tenho uma ideia <span class="ml-2" aria-hidden="true">→</span></a>@endcan<a class="apple-button--light" href="{{ route('ideas.index', ['mine'=>1]) }}">Acompanhar minhas ideias</a>@if(auth()->user()->canViewPublicIdeas())<a class="apple-button--light" href="{{ route('cycles.index') }}">Participar das votações</a>@endif</div></div>
                </div>
                <span class="welcome-decoration" aria-hidden="true"><x-sidebar-icon name="idea" /></span>
            </section>
            <section aria-label="Indicadores do painel" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach([
                    ['label'=>'Total de ideias','value'=>$stats['total'],'icon'=>'idea','tone'=>'brand','description'=>'Propostas registradas'],
                    ['label'=>'Aguardando revisão','value'=>$stats['in_evaluation'],'icon'=>'check','tone'=>'amber','description'=>'Ideias recebidas para conferir'],
                    ['label'=>'Aprovadas','value'=>$stats['approved'],'icon'=>'check','tone'=>'emerald','description'=>'Prontas para avançar'],
                    ['label'=>'Em implementação','value'=>$stats['implementation'],'icon'=>'settings','tone'=>'violet','description'=>'Melhorias em execução'],
                    ['label'=>'Votações abertas','value'=>$stats['open_votes'],'icon'=>'vote','tone'=>'sky','description'=>'Abertas ou programadas'],
                ] as $stat)
                    <x-stat-card :label="$stat['label']" :value="$stat['value']" :icon="$stat['icon']" :tone="$stat['tone']" :description="$stat['description']" :index="$loop->index" />
                @endforeach
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
                <div class="apple-card p-6">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <h3 class="text-xl font-bold tracking-[-0.04em] text-slate-900">Ideias recentes</h3>
                        <a href="{{ route('ideas.index') }}" class="text-sm font-medium text-sky-600 hover:text-sky-700">Ver todas</a>
                    </div>

                    <div class="stack-4">
                        @forelse ($recentIdeas as $idea)
                            <div class="idea-preview rounded-2xl border border-slate-200 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <a class="text-lg font-semibold text-slate-900 hover:text-sky-700" href="{{ route('ideas.show',$idea) }}">{{ (auth()->user()->can('evaluate', $idea) || $idea->isContributor(auth()->user())) ? $idea->title : ($idea->publication['title'] ?? 'Publicação em revisão') }}</a>
                                        <p class="mt-1 text-sm text-slate-500">{{ (auth()->user()->can('evaluate', $idea) || $idea->isContributor(auth()->user())) ? $idea->originSector?->name : implode(', ', $idea->publication['sectors'] ?? []) }} · {{ $idea->visibleAuthorName(auth()->user()) }}</p>
                                    </div>
                                    <x-idea-status-badge :status="$idea->status" />
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ Str::limit((auth()->user()->can('evaluate', $idea) || $idea->isContributor(auth()->user())) ? $idea->problem_description : ($idea->publication['problem'] ?? ''), 150) }}</p>
                            </div>
                        @empty
                            <p class="rounded-[22px] border border-dashed border-slate-300 bg-slate-50/60 p-5 text-sm text-slate-500">Nenhuma ideia recente.</p>
                        @endforelse
                    </div>
                </div>

                <div class="apple-card p-6">
                    <h3 class="text-xl font-bold tracking-[-0.04em] text-slate-900">Seus avisos</h3><a class="text-sm text-sky-700 underline" href="{{ route('notifications.index') }}">Ver todos os avisos</a>
                    <div class="mt-5 stack-3">
                        @forelse ($notifications as $notification)
                            <div class="notification-card rounded-[18px] border border-slate-200 p-4">
                                <p class="text-sm font-semibold text-slate-800">{{ $notification->title }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->message }}</p>
                                @if (!$notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf<button class="mt-2 text-sm text-sky-700">Marcar como lida</button></form>@endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhuma notificação recente.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <section class="apple-card stack-3 p-6"><h3 class="text-xl font-bold">Minha participação e reconhecimentos</h3><p>Votos registrados: {{ $participation }} · Ideias concluídas: {{ $stats['completed'] }}</p>@forelse ($awards as $award)<p>{{ $award->idea->idea_code }} — {{ $award->category }}: R$ {{ number_format($award->award_amount,2,',','.') }}</p>@empty<p>Nenhum reconhecimento divulgado.</p>@endforelse</section>

        </div>
    </div>
</x-app-layout>
