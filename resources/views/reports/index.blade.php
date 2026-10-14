<x-app-layout>
<x-slot name="header"><h2 class="text-2xl font-bold">Indicadores e relatórios</h2></x-slot>
<div class="mx-auto max-w-6xl space-y-6 px-4 py-8">
    <form class="apple-card grid gap-3 p-6 md:grid-cols-3" method="GET">
        <x-workflow-field name="from" label="De" type="date" :required="false" :value="request('from')" /><x-workflow-field name="to" label="Até" type="date" :required="false" :value="request('to')" />
        <label>Setor<select name="sector" class="block w-full rounded-lg"><option value="">Todos</option>@foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(request('sector') == $sector->id)>{{ $sector->name }}</option>@endforeach</select></label>
        <label>Status<select name="status" class="block w-full rounded-lg"><option value="">Todos</option>@foreach (\App\Models\Idea::LABELS as $status=>$label)<option value="{{ $status }}" @selected(request('status') == $status)>{{ $label }}</option>@endforeach</select></label>
        <x-workflow-field name="category" label="Categoria" :required="false" :value="request('category')" /><button class="apple-button">Aplicar filtros</button>
    </form>
    <div class="grid gap-4 md:grid-cols-4"><div class="apple-card p-5">Recebidas: <strong>{{ $total }}</strong></div><div class="apple-card p-5">Resultados validados: <strong>{{ $implemented }}</strong><p>Taxa: {{ $total ? number_format($implemented/$total*100,1,',','.') : '0' }}%</p></div><div class="apple-card p-5">Tempo médio até execução: <strong>{{ number_format($durations->avg() ?? 0,1,',','.') }} dias</strong><p>{{ $durations->count() }} implementações com data de conclusão.</p></div><div class="apple-card p-5">Reconhecimentos aprovados: {{ $awards->count() }}<p>R$ {{ number_format($awards->sum('award_amount'),2,',','.') }}</p></div></div>
    <section class="apple-card space-y-3 p-6"><h3 class="text-xl font-bold">Distribuição por status</h3>@foreach ($distribution as $status=>$count)<div><p>{{ \App\Models\Idea::LABELS[$status] ?? $status }}: {{ $count }}</p><progress class="w-full" value="{{ $count }}" max="{{ max(1,$total) }}">{{ $count }}/{{ $total }}</progress></div>@endforeach</section>
    <a class="apple-button" href="{{ route('reports.export', request()->query()) }}">Exportar CSV com indicadores validados</a>
    <p>Indicadores de unidades diferentes são apresentados separadamente. Estimativas não são exportadas como resultados comprovados. Autoria e votos individuais não constam no arquivo.</p>
    <section class="apple-card overflow-x-auto p-6"><table class="w-full text-left"><thead><tr><th>Código</th><th>Publicação</th><th>Status</th><th>Validação</th></tr></thead><tbody>@foreach ($ideas as $idea)<tr><td class="p-2">{{ $idea->idea_code }}</td><td class="p-2">{{ $idea->publication['title'] ?? 'Publicação pendente' }}</td><td class="p-2">{{ \App\Models\Idea::LABELS[$idea->status] ?? $idea->status }}</td><td class="p-2">{{ $idea->result?->validated_at?->format('d/m/Y') ?? 'Pendente' }}</td></tr>@endforeach</tbody></table>{{ $ideas->links() }}</section>
</div>
</x-app-layout>
