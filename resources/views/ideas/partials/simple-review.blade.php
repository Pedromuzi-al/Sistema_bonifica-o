<section class="apple-card space-y-4 p-6" id="revisao-da-ideia">
    <h3 class="text-xl font-bold">Revisar ideia</h3>
    <p>Confira se a ideia é compreensível, se já existe uma semelhante e se o texto pode ser divulgado. Libere para votação, peça ajuste ou arquive com justificativa. Liberar não aprova a execução nem concede prêmio.</p>
    <form method="POST" action="{{ route('ideas.review-decision', $idea) }}" class="space-y-4">@csrf
        <p class="text-sm">O texto já vem preenchido. Altere somente o que precisar para os funcionários entenderem a proposta.</p>
        @foreach(['title'=>'Título público','problem'=>'Problema','solution'=>'Solução proposta','results'=>'Benefícios esperados (opcional)','category'=>'Categoria (opcional)'] as $field=>$label)
            <x-workflow-field :name="$field" :label="$label" :type="in_array($field,['title','category']) ? 'text' : 'textarea'" :required="false" :value="$idea->publication[$field] ?? match($field) { 'title'=>$idea->title, 'problem'=>$idea->problem_description, 'solution'=>$idea->solution_description, 'results'=>$idea->expected_results, default=>'' }" />
        @endforeach
        <label class="block"><input type="checkbox" name="privacy_reviewed" value="1" @checked(old('privacy_reviewed'))> Conferi o texto e removi dados pessoais e informações confidenciais (obrigatório para liberar).</label>
        <x-workflow-field name="notes" label="Justificativa da decisão ou o que precisa ser ajustado" type="textarea" minlength="10" maxlength="10000" />
        <div class="flex flex-wrap gap-3">
            <button class="apple-button" name="decision" value="release">Liberar para votação</button>
            <button class="apple-button--light" name="decision" value="adjust">Pedir ajuste</button>
            <button class="apple-button--light" name="decision" value="archive">Arquivar</button>
        </div>
    </form>
</section>
