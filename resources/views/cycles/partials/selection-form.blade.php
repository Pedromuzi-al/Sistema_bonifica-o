<form data-cycle-purpose="selection" method="POST" action="{{ route('cycles.store') }}" class="apple-card space-y-5 p-6" x-data x-on:invalid.capture="$event.target.closest('details')?.setAttribute('open', '')">
    @csrf
    <input type="hidden" name="purpose" value="selection">
    <input type="hidden" name="timezone" value="{{ config('innovation.cycle_timezone') }}">
    <h3 class="text-2xl font-bold">Criar ciclo de seleção de ideias</h3>
    <p>Registre → revise e libere → vote no potencial → selecione → implemente → comprove os resultados.</p>
    <p class="text-sm text-slate-600">Podem participar propostas ainda não implementadas, em execução ou já implementadas. Basta revisar a ideia e usar Liberar para votação, retirando dados pessoais e informações confidenciais.</p>
    <x-workflow-field name="name" label="Nome do ciclo" placeholder="Ex.: Ideias para melhorar nosso trabalho" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-workflow-field name="starts_at" label="Início da votação" type="datetime-local" :value="now()->setTimezone(config('innovation.cycle_timezone'))->addMinutes(10)->format('Y-m-d\TH:i')" />
        <x-workflow-field name="ends_at" label="Fim da votação" type="datetime-local" :value="now()->setTimezone(config('innovation.cycle_timezone'))->addDays(7)->format('Y-m-d\TH:i')" />
        <x-workflow-field name="max_selections" label="Quantas ideias podem avançar?" type="number" value="5" min="1" max="1000" />
        <x-workflow-field name="minimum_votes" label="Mínimo de votos por ideia" type="number" value="1" min="1" max="100000" />
    </div>
    <p class="text-sm text-slate-500">Horários em {{ config('innovation.cycle_timezone') }}. Pode começar e terminar hoje; reserve alguns minutos antes do início para escolher as propostas e confirmar a abertura.</p>
    <p class="text-sm">Os funcionários avaliam de 1 a 5: utilidade (34%), relevância (33%) e benefício esperado (33%). A votação organiza as prioridades. A equipe decide quais avançam, justificando a viabilidade e os recursos disponíveis.</p>
    <details class="space-y-4"><summary class="cursor-pointer font-semibold">Reconhecimento pela ideia e regras adicionais (opcional)</summary>
        <x-workflow-field name="reward_amount" label="Bonificação por ideia selecionada (R$)" type="number" value="0" min="0" step="0.01" />
        <p class="text-sm text-slate-600">Zero significa seleção sem bonificação financeira. Um valor maior gera uma proposta de reconhecimento após a decisão, sujeita a aprovação e dividida entre os coautores. O reconhecimento pelos resultados pode acontecer depois, em outro ciclo.</p>
        <x-workflow-field name="minimum_score" label="Nota mínima para selecionar (0 a 100)" type="number" value="0" min="0" max="100" step="0.01" />
        <x-workflow-field name="anomaly_threshold" label="Votos idênticos para gerar alerta de revisão" type="number" value="5" min="2" max="100000" />
        <label class="block text-sm">Permitir deixar critérios sem nota?<select name="allow_abstention" class="mt-1 block w-full rounded-lg"><option value="0" @selected(!old('allow_abstention'))>Não, responder todos</option><option value="1" @selected(old('allow_abstention'))>Sim, responder pelo menos um</option></select></label>
    </details>
    <button class="apple-button">Continuar para escolher as propostas</button>
    <p class="text-sm text-slate-500">Primeiro será salvo um rascunho. Ideias não selecionadas ficam no banco e podem participar de outro ciclo.</p>
</form>
