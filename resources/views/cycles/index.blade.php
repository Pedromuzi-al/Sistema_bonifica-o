<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Votações e reconhecimentos</h2></x-slot>
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-8">
        @if ($canManage)
            @php
                $defaults = [
                    ['name' => 'Impacto operacional', 'description' => 'De 1 (pouco benefício) a 5 (grande melhoria na operação).', 'weight' => 20],
                    ['name' => 'Abrangência', 'description' => 'De 1 (benefício restrito) a 5 (benefício para vários setores).', 'weight' => 20],
                    ['name' => 'Eficiência', 'description' => 'De 1 (pouca economia) a 5 (grande economia de tempo ou recursos).', 'weight' => 20],
                    ['name' => 'Qualidade', 'description' => 'De 1 (pouca melhoria) a 5 (grande melhoria na qualidade).', 'weight' => 20],
                    ['name' => 'Inovação', 'description' => 'De 1 (solução já conhecida) a 5 (solução muito inovadora).', 'weight' => 20],
                ];
                $indicators = old('result_indicators', [['indicator' => '', 'unit' => '', 'direction' => 'decrease', 'target' => '']]);
            @endphp
            <form method="POST" action="{{ route('cycles.store') }}" class="space-y-5"
                x-on:invalid.capture="$event.target.closest('details')?.setAttribute('open', '')"
                x-data="{ indicators: @js($indicators), options: @js($indicatorOptions), weights: @js(old('weights', ['real_results' => 50, 'technical' => 30, 'votes' => 20])), criteria: @js(old('criteria', $defaults)),
                    get weightTotal() { return Object.values(this.weights).reduce((sum, value) => sum + Number(value || 0), 0) },
                    get criteriaTotal() { return this.criteria.reduce((sum, item) => sum + Number(item.weight || 0), 0) },
                    chooseIndicator(row, value) { if (value === '') return; const option = this.options[Number(value)]; row.indicator = option.indicator; row.unit = option.unit; }
                }">
                @csrf
                <div>
                    <h3 class="text-2xl font-bold">Criar um ciclo</h3>
                    <p class="mt-2 text-sm text-slate-600">Defina o período, a meta e o reconhecimento. As regras de avaliação já vêm preenchidas.</p>
                </div>
                <section class="apple-card space-y-4 p-6">
                    <h4 class="text-lg font-semibold">1. Quando acontece?</h4>
                    <x-workflow-field name="name" label="Nome do ciclo" placeholder="Ex.: Reconhecimento de outubro" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-workflow-field name="starts_at" label="Início da votação" type="datetime-local" :value="now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i')" />
                        <x-workflow-field name="ends_at" label="Fim da votação" type="datetime-local" :value="now()->addDays(8)->setTime(18, 0)->format('Y-m-d\TH:i')" />
                    </div>
                    @error('starts_at')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    @error('ends_at')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    <p class="text-sm text-slate-500">Horários em {{ config('app.timezone') }}. Na próxima tela, selecione as ideias e abra o ciclo antes do início.</p>
                </section>
                <section class="apple-card space-y-4 p-6">
                    <h4 class="text-lg font-semibold">2. Qual resultado será reconhecido?</h4>
                    <p class="text-sm text-slate-600">Escolha um indicador já registrado nas ideias ou informe um novo. A meta é o ganho esperado, na mesma unidade.</p>
                    <template x-for="(row, index) in indicators" :key="index">
                        <div class="space-y-4 rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold" x-text="'Resultado ' + (index + 1)"></span>
                                <button type="button" x-show="indicators.length > 1" @click="indicators.splice(index, 1)" class="text-sm text-rose-600">Remover</button>
                            </div>
                            <label class="block text-sm font-medium">Usar indicador de uma ideia
                                <select class="mt-1 block w-full rounded-lg border-slate-300" @change="chooseIndicator(row, $event.target.value)">
                                    <option value="">Preencher manualmente</option>
                                    <template x-for="(option, optionIndex) in options" :key="optionIndex"><option :value="optionIndex" x-text="option.indicator + ' (' + option.unit + ')'" :selected="row.indicator === option.indicator && row.unit === option.unit"></option></template>
                                </select>
                            </label>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block text-sm font-medium">Indicador<input required x-model="row.indicator" :name="`result_indicators[${index}][indicator]`" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Ex.: Tempo de atendimento"></label>
                                <label class="block text-sm font-medium">Unidade<input required x-model="row.unit" :name="`result_indicators[${index}][unit]`" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Ex.: minutos"></label>
                                <label class="block text-sm font-medium">O que melhora?<select x-model="row.direction" :name="`result_indicators[${index}][direction]`" class="mt-1 block w-full rounded-lg border-slate-300"><option value="decrease">Diminuir (tempo, custo, erros)</option><option value="increase">Aumentar (produção, receita, qualidade)</option><option value="qualitative">Atingir uma nota numa escala</option></select></label>
                                <label class="block text-sm font-medium"><span x-text="row.direction === 'qualitative' ? 'Nota máxima da escala' : 'Meta de melhoria'"></span><input required type="number" min="0.01" step="any" x-model="row.target" :name="`result_indicators[${index}][target]`" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Ex.: 10"></label>
                            </div>
                            <p class="text-sm text-slate-500" x-show="row.direction !== 'qualitative'">Exemplo: reduzir de 30 para 20 minutos significa uma melhoria de 10 minutos.</p>
                            <p class="text-sm text-slate-500" x-show="row.direction === 'qualitative'">Informe o máximo da escala utilizada nos resultados validados da ideia.</p>
                        </div>
                    </template>
                    <button type="button" class="apple-button--light" @click="indicators.push({ indicator: '', unit: '', direction: 'decrease', target: '' })" :disabled="indicators.length >= 20">Adicionar outro resultado</button>
                    <p class="text-sm text-slate-500">O nome e a unidade devem corresponder aos resultados da ideia. Ao abrir o ciclo, cada ideia precisa ter pelo menos um indicador compatível.</p>
                </section>
                <section class="apple-card space-y-4 p-6">
                    <h4 class="text-lg font-semibold">3. Como será o reconhecimento?</h4>
                    <x-workflow-field name="category" label="Nome do reconhecimento" value="Destaque em inovação" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-workflow-field name="max_awards" label="Quantas ideias podem ganhar?" type="number" value="1" min="1" max="1000" />
                        <x-workflow-field name="reward_amount" label="Valor por ideia (R$)" type="number" value="0" min="0" step="0.01" />
                    </div>
                    <p class="text-sm text-slate-500">Deixe o valor em zero para reconhecimento sem dinheiro. Os vencedores serão propostos após a votação e precisam de aprovação.</p>
                </section>
                <details class="rounded-2xl border border-slate-200 bg-white/70 p-6" @if ($errors->any()) open @endif>
                    <summary class="cursor-pointer font-semibold">Ajustar regras de avaliação <span class="text-sm font-normal text-slate-500">(opcional)</span></summary>
                    <div class="mt-5 space-y-6">
                        <fieldset class="space-y-3">
                            <legend class="font-semibold">Composição da nota final</legend>
                            <div class="grid gap-4 sm:grid-cols-3">
                                @foreach (['real_results' => 'Resultados reais', 'technical' => 'Avaliação técnica', 'votes' => 'Votação'] as $field => $label)
                                    <label class="text-sm">{{ $label }} (%)<input required type="number" min="0" max="100" step="0.01" name="weights[{{ $field }}]" x-model="weights.{{ $field }}" class="mt-1 block w-full rounded-lg border-slate-300"></label>
                                @endforeach
                            </div>
                            <p class="text-sm" :class="Math.abs(weightTotal - 100) < 0.001 ? 'text-emerald-700' : 'text-rose-600'" x-text="'Total: ' + weightTotal + '% — deve somar 100%'"></p>
                        </fieldset>
                        <fieldset class="space-y-3">
                            <legend class="font-semibold">O que os funcionários avaliam</legend>
                            <p class="text-sm text-slate-600">Cada critério recebe uma nota de 1 a 5. As orientações já estão prontas para usar.</p>
                            <template x-for="(criterion, index) in criteria" :key="index">
                                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-[1fr_100px]">
                                    <label class="text-sm">Critério<input required x-model="criterion.name" :name="`criteria[${index}][name]`" class="mt-1 block w-full rounded-lg border-slate-300"></label>
                                    <label class="text-sm">Peso (%)<input required type="number" min="0.01" max="100" step="0.01" x-model="criterion.weight" :name="`criteria[${index}][weight]`" class="mt-1 block w-full rounded-lg border-slate-300"></label>
                                    <label class="text-sm sm:col-span-2">Orientação para votar<input required x-model="criterion.description" :name="`criteria[${index}][description]`" class="mt-1 block w-full rounded-lg border-slate-300"></label>
                                </div>
                            </template>
                            <p class="text-sm" :class="Math.abs(criteriaTotal - 100) < 0.001 ? 'text-emerald-700' : 'text-rose-600'" x-text="'Total dos critérios: ' + criteriaTotal + '% — deve somar 100%'"></p>
                        </fieldset>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-workflow-field name="minimum_votes" label="Mínimo de votos por ideia" type="number" value="1" min="1" max="100000" />
                            <x-workflow-field name="minimum_score" label="Nota mínima para ganhar (0 a 100)" type="number" value="70" min="0" max="100" step="0.01" />
                        </div>
                        <label class="block text-sm font-medium">Permitir deixar critérios sem nota?<select name="allow_abstention" class="mt-1 block w-full rounded-lg border-slate-300"><option value="0" @selected(!old('allow_abstention', false))>Não, responder todos</option><option value="1" @selected(old('allow_abstention', false))>Sim, responder pelo menos um</option></select></label>
                    </div>
                </details>
                <details class="apple-card space-y-4 p-6" x-data="{ bands: @js(old('categories', [])) }"><summary class="cursor-pointer font-semibold">Categorias e faixas de reconhecimento (opcional)</summary><p class="text-sm">Sem faixas, vale o reconhecimento principal acima. Com faixas, cada ideia recebe no máximo uma categoria, na ordem definida. Use intervalos sem sobreposição e informe valores por ideia, divididos entre os participantes.</p><template x-for="(band,index) in bands" :key="index"><div class="grid gap-3 rounded-xl border p-4 sm:grid-cols-2"><label>Categoria<input required x-model="band.name" :name="`categories[${index}][name]`" class="block w-full rounded-lg"></label><label>Nota mínima<input required type="number" min="0" max="100" step="0.01" x-model="band.minimum_score" :name="`categories[${index}][minimum_score]`" class="block w-full rounded-lg"></label><label>Nota máxima<input required type="number" min="0" max="100" step="0.01" x-model="band.maximum_score" :name="`categories[${index}][maximum_score]`" class="block w-full rounded-lg"></label><label>Valor por ideia (R$)<input required type="number" min="0" step="0.01" x-model="band.reward_amount" :name="`categories[${index}][reward_amount]`" class="block w-full rounded-lg"></label><label>Limite de ideias premiadas<input required type="number" min="1" max="1000" x-model="band.max_awards" :name="`categories[${index}][max_awards]`" class="block w-full rounded-lg"></label><button type="button" @click="bands.splice(index,1)" class="text-rose-700">Remover categoria</button></div></template><button type="button" class="apple-button--light" @click="bands.push({name:'',minimum_score:70,maximum_score:100,reward_amount:0,max_awards:1})" :disabled="bands.length >= 20">Adicionar categoria</button></details>
                <details class="apple-card space-y-4 p-6"><summary class="font-semibold">Desempate e alertas (opcional)</summary><p class="text-sm">Após a nota final, aplique as três dimensões na ordem escolhida, sem repetir. Persistindo empate, vale o código da ideia em ordem crescente.</p>@foreach(['real_results'=>'Resultados reais','technical'=>'Avaliação técnica','votes'=>'Votação'] as $default=>$label)<label class="block">{{ $loop->iteration }}º desempate<select name="tie_break_order[]" class="block w-full rounded-lg">@foreach(['real_results'=>'Resultados reais','technical'=>'Avaliação técnica','votes'=>'Votação'] as $key=>$text)<option value="{{ $key }}" @selected(old('tie_break_order.'.$loop->parent->index, $default) === $key)>{{ $text }}</option>@endforeach</select></label>@endforeach<x-workflow-field name="anomaly_threshold" label="Quantidade de votos idênticos para gerar alerta" type="number" value="5" min="2" max="100000" /></details>
                <div class="rounded-2xl bg-sky-50 p-4 text-sm text-sky-900">
                    <p class="font-semibold">Regras que serão usadas</p>
                    <p class="mt-1"><span x-text="weights.real_results"></span>% resultados · <span x-text="weights.technical"></span>% avaliação técnica · <span x-text="weights.votes"></span>% votação. Confira as demais condições em “Ajustar regras de avaliação”.</p>
                </div>
                <button class="apple-button" :disabled="Math.abs(weightTotal - 100) > 0.001 || Math.abs(criteriaTotal - 100) > 0.001">Continuar para selecionar ideias →</button>
                <p class="text-sm text-slate-500">Esta etapa salva um rascunho. A votação só será programada depois de selecionar as ideias e abrir o ciclo.</p>
            </form>
        @endif
        <section class="space-y-4">
            <h3 class="text-xl font-bold">Ciclos cadastrados</h3>
            @forelse ($cycles as $cycle)
                <a class="apple-card block p-6" href="{{ route('cycles.show', $cycle) }}">
                    <div class="flex flex-wrap items-center justify-between gap-2"><h4 class="font-bold">{{ $cycle->name }}</h4><span class="apple-pill">{{ ['draft' => 'Rascunho', 'open' => 'Aberto / programado', 'closed' => 'Encerrado', 'cancelled' => 'Cancelado'][$cycle->status] ?? $cycle->status }}</span></div>
                    <p class="mt-2 text-sm text-slate-500">{{ $cycle->starts_at->format('d/m/Y H:i') }} a {{ $cycle->ends_at->format('d/m/Y H:i') }}</p>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-6 text-sm text-slate-500">Nenhum ciclo cadastrado ainda.</p>
            @endforelse
            {{ $cycles->links() }}
        </section>
    </div>
</x-app-layout>
