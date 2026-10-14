<x-app-layout>
<x-slot name="header"><h2 class="text-2xl font-bold">Ciclos de votação e reconhecimento</h2></x-slot>
<div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
    @foreach ($cycles as $cycle)
        @if ($canManage || in_array($cycle->status,['open','closed']))<a class="apple-card block p-6" href="{{ route('cycles.show', $cycle) }}"><h3 class="font-bold">{{ $cycle->name }}</h3><p>{{ ['draft'=>'Rascunho','open'=>'Aberto / programado','closed'=>'Encerrado'][$cycle->status] ?? $cycle->status }} · {{ $cycle->starts_at->format('d/m/Y H:i') }} a {{ $cycle->ends_at->format('d/m/Y H:i') }}</p></a>@endif
    @endforeach
    {{ $cycles->links() }}
    @if ($canManage)
    <form class="apple-card space-y-4 p-6" method="POST" action="{{ route('cycles.store') }}" x-data="{ indicators: 1 }">@csrf
        <h3 class="text-xl font-bold">Configurar novo ciclo</h3>
        <p class="text-sm">Configure e abra antes do horário inicial. As regras, publicações e pontuações técnicas serão congeladas. Todos os horários usam {{ config('app.timezone') }}.</p>
        <x-workflow-field name="name" label="Nome do ciclo" /><x-workflow-field name="starts_at" label="Início" type="datetime-local" /><x-workflow-field name="ends_at" label="Encerramento" type="datetime-local" />
        <fieldset class="space-y-3 rounded-lg border p-4"><legend>Pesos das dimensões — soma de 100%</legend>
            @foreach (['real_results'=>['Resultados reais',50], 'technical'=>['Avaliação técnica',30], 'votes'=>['Votação',20]] as $field=>$setting)<x-workflow-field name="weights[{{ $field }}]" :label="$setting[0]" type="number" :value="$setting[1]" min="0" max="100" step="0.01" />@endforeach
        </fieldset>
        <fieldset class="space-y-4 rounded-lg border p-4"><legend>Critérios de votação — soma de 100%</legend>
        @foreach (['Impacto operacional','Abrangência','Eficiência','Qualidade','Inovação'] as $i=>$name)
            <div class="space-y-2"><x-workflow-field name="criteria[{{ $i }}][name]" label="Nome do critério" :value="$name" /><x-workflow-field name="criteria[{{ $i }}][description]" label="Orientação para notas de 1 a 5" /><x-workflow-field name="criteria[{{ $i }}][weight]" label="Peso (%)" type="number" value="20" min="0.01" max="100" step="0.01" /></div>
        @endforeach
        </fieldset>
        <fieldset class="space-y-4 rounded-lg border p-4"><legend>Metodologia para resultados reais</legend>
            <p>Cada indicador será normalizado de 0 a 100, pelo ganho medido dividido pela meta. A dimensão será a média dos indicadores compatíveis. Para resultados qualitativos, informe uma escala explícita e sua nota validada no campo “situação posterior”. Indicadores sem dados não recebem valores inventados.</p>
            @for ($i=0; $i<20; $i++)<fieldset @disabled($i > 0) x-show="indicators > {{ $i }}" :disabled="indicators <= {{ $i }}" class="space-y-2 border-b pb-4">
                <x-workflow-field name="result_indicators[{{ $i }}][indicator]" label="Nome exato do indicador" /><x-workflow-field name="result_indicators[{{ $i }}][unit]" label="Unidade exata / escala qualitativa" />
                <label>Direção do benefício<select name="result_indicators[{{ $i }}][direction]" class="block w-full rounded-lg"><option value="decrease">Redução</option><option value="increase">Aumento</option><option value="qualitative">Nota qualitativa validada</option></select></label>
                <x-workflow-field name="result_indicators[{{ $i }}][target]" label="Meta de ganho / nota máxima da escala" type="number" min="0.01" step="any" />
            </fieldset>@endfor
            <button class="apple-button--light" type="button" @click="indicators=Math.min(20,indicators+1)">Adicionar metodologia</button>
        </fieldset>
        <x-workflow-field name="minimum_votes" label="Mínimo de votos para apuração elegível" type="number" min="1" value="1" />
        <label>Abstenção por critério<select name="allow_abstention" class="block w-full rounded-lg"><option value="0">Desabilitada</option><option value="1">Habilitada — exige pelo menos uma nota</option></select></label>
        <x-workflow-field name="category" label="Categoria de reconhecimento" /><x-workflow-field name="minimum_score" label="Pontuação mínima (0 a 100)" type="number" min="0" max="100" step="0.01" value="70" /><x-workflow-field name="reward_amount" label="Valor por premiado (R$; zero para reconhecimento sem valor)" type="number" min="0" step="0.01" value="0" /><x-workflow-field name="max_awards" label="Quantidade máxima de premiados" type="number" min="1" value="1" />
        <p>Desempate: maior resultado real, maior nota técnica, maior nota da votação e, persistindo empate, código da ideia em ordem alfabética. Cinco votos com notas idênticas geram um alerta para revisão, sem afirmar fraude.</p>
        <button class="apple-button">Criar rascunho</button>
    </form>
    @endif
</div>
</x-app-layout>
