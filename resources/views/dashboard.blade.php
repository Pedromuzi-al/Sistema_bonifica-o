<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Visão geral</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-slate-900">Painel corporativo</h2>
            </div>
            @if (auth()->user()->can('create', App\Models\Idea::class))
                <a href="{{ route('ideas.create') }}" data-permission="true" data-permission-message="Você não tem permissão para criar uma nova ideia." class="apple-button">Nova ideia</a>
            @else
                <button type="button" data-permission="false" data-permission-message="Você não tem permissão para criar uma nova ideia." class="apple-button">Nova ideia</button>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @unless(auth()->user()->canViewPublicIdeas())<section class="apple-card space-y-2 p-5"><h3 class="font-bold">Sua conta aguarda liberação</h3><p>Peça ao administrador para liberar seu acesso. Depois disso, você poderá enviar ideias e participar das votações.</p></section>@endunless
            <section class="apple-card space-y-3 p-5"><h3 class="text-xl font-bold">Por onde começar?</h3><div class="flex flex-wrap gap-3">@can('create', App\Models\Idea::class)<a class="apple-button" href="{{ route('ideas.create') }}">Tenho uma ideia</a>@endcan<a class="apple-button--light" href="{{ route('ideas.index', ['mine'=>1]) }}">Acompanhar minhas ideias</a>@if(auth()->user()->canViewPublicIdeas())<a class="apple-button--light" href="{{ route('cycles.index') }}">Quero participar de uma votação</a>@endif</div></section>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="apple-card p-5">
                    <p class="text-sm text-slate-500">Total de ideias</p>
                    <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-900">{{ $stats['total'] }}</p>
                </div>
                <div class="apple-card p-5">
                    <p class="text-sm text-slate-500">Em avaliação</p>
                    <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-amber-600">{{ $stats['in_evaluation'] }}</p>
                </div>
                <div class="apple-card p-5">
                    <p class="text-sm text-slate-500">Aprovadas</p>
                    <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-emerald-600">{{ $stats['approved'] }}</p>
                </div>
                <div class="apple-card p-5">
                    <p class="text-sm text-slate-500">Em implementação</p>
                    <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-sky-600">{{ $stats['implementation'] }}</p>
                </div>
                <div class="apple-card p-5">
                    <p class="text-sm text-slate-500">Votações abertas</p>
                    <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-sky-600">{{ $stats['open_votes'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
                <div class="apple-card p-6">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <h3 class="text-xl font-bold tracking-[-0.04em] text-slate-900">Ideias recentes</h3>
                        <a href="{{ route('ideas.index') }}" class="text-sm font-medium text-sky-600 hover:text-sky-700">Ver todas</a>
                    </div>

                    <div class="space-y-4">
                        @forelse ($recentIdeas as $idea)
                            <div class="rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-lg font-semibold text-slate-900">{{ (auth()->user()->can('evaluate', $idea) || auth()->id() == $idea->author_id) ? $idea->title : ($idea->publication['title'] ?? 'Publicação em revisão') }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ (auth()->user()->can('evaluate', $idea) || auth()->id() == $idea->author_id) ? $idea->originSector?->name : implode(', ', $idea->publication['sectors'] ?? []) }} · {{ $idea->visibleAuthorName(auth()->user()) }}</p>
                                    </div>
                                    <x-idea-status-badge :status="$idea->status" />
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ Str::limit((auth()->user()->can('evaluate', $idea) || auth()->id() == $idea->author_id) ? $idea->problem_description : ($idea->publication['problem'] ?? ''), 150) }}</p>
                            </div>
                        @empty
                            <p class="rounded-[22px] border border-dashed border-slate-300 bg-slate-50/60 p-5 text-sm text-slate-500">Nenhuma ideia recente.</p>
                        @endforelse
                    </div>
                </div>

                <div class="apple-card p-6">
                    <h3 class="text-xl font-bold tracking-[-0.04em] text-slate-900">Seus avisos</h3><a class="text-sm text-sky-700 underline" href="{{ route('notifications.index') }}">Ver todos os avisos</a>
                    <div class="mt-5 space-y-3">
                        @forelse ($notifications as $notification)
                            <div class="rounded-[18px] border border-slate-200 bg-slate-50/75 p-4">
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

            <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">Minha participação e reconhecimentos</h3><p>Votos registrados: {{ $participation }} · Ideias concluídas: {{ $stats['completed'] }}</p>@forelse ($awards as $award)<p>{{ $award->idea->idea_code }} — {{ $award->category }}: R$ {{ number_format($award->award_amount,2,',','.') }}</p>@empty<p>Nenhum reconhecimento divulgado.</p>@endforelse</section>
            <div class="apple-card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-xl font-bold tracking-[-0.04em] text-slate-900">Atalhos</h3>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('ideas.index', ['implemented'=>1]) }}" class="apple-button--light">Ideias Implementadas</a>
                    <a href="{{ route('cycles.index') }}" class="apple-button--light">Votações e resultados</a>
                    @can('manage-admin')<a href="{{ route('reports.index') }}" class="apple-button--light">Relatórios</a>@endcan

                    @if (auth()->user()->can('create', App\Models\Idea::class))
                        <a href="{{ route('ideas.create') }}" data-permission="true" data-permission-message="Você não tem permissão para criar ideias." class="apple-button">Cadastrar ideia</a>
                    @else
                        <button type="button" data-permission="false" data-permission-message="Você não tem permissão para criar ideias." class="apple-button">Cadastrar ideia</button>
                    @endif
                    <a href="{{ route('ideas.index', ['mine'=>1]) }}" class="apple-button--light">Minhas ideias</a>
                    @php($canEvaluate = auth()->user()->can('evaluate', new App\Models\Idea))
                    @if ($canEvaluate)
                        <a href="{{ route('evaluation.index') }}" data-permission="true" data-permission-message="Você não tem permissão para acessar a avaliação." class="apple-button--primary">Acompanhar avaliação</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
