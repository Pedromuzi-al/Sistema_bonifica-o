<dialog id="idea-delete-dialog" class="idea-delete-dialog" aria-labelledby="delete-ideas-title" aria-describedby="delete-ideas-description"
    x-data="{ selected: @js(array_map('strval', is_array(old('ids')) ? array_filter(old('ids'), 'is_scalar') : [])), confirming: false, acknowledged: false }"
    @close="selected = []; confirming = false; acknowledged = false"
    @if($errors->has('ids') || $errors->has('ids.*') || $errors->has('deletion_reason') || $errors->has('confirm_deletion')) x-init="$el.showModal()" @endif>
    <div class="stack-5 p-5 sm:p-7">
        <div class="flex items-center justify-between gap-4">
            <h2 id="delete-ideas-title" class="text-xl font-bold">Excluir ideias</h2>
            <button type="button" class="apple-button--light" autofocus @click="$el.closest('dialog').close()" aria-label="Fechar seleção de ideias">Fechar</button>
        </div>
        <p id="delete-ideas-description">As ideias serão retiradas das listagens. O histórico e os documentos privados serão preservados; esta ação não apaga definitivamente os dados.</p>
        <form method="POST" action="{{ route('ideas.bulk-destroy') }}" class="stack-5" @submit="if (!confirming) { $event.preventDefault(); confirming = true; $nextTick(() => $refs.reason.focus()); }">
            @csrf @method('DELETE')
            <div x-show="!confirming" class="stack-3">
                <p class="text-sm">Selecione entre as ideias desta página. Para localizar outras, feche e use os filtros ou a paginação da listagem.</p>
                <div class="max-h-72 stack-2 overflow-y-auto">
                    @forelse($ideas as $candidate)
                        @php($blocker = $deletionBlockers[$candidate->id] ?? null)
                        <label class="block rounded-xl border border-slate-300 p-3">
                            <span class="flex items-start gap-3"><input type="checkbox" name="ids[]" value="{{ $candidate->id }}" x-model="selected" @disabled($blocker)><span><strong>{{ $candidate->title }}</strong><span class="block text-sm">{{ $candidate->idea_code }} · {{ \App\Models\Idea::LABELS[$candidate->status] ?? $candidate->status }}</span></span></span>
                            @if($blocker)<span class="mt-2 block text-sm">Não pode excluir: {{ $blocker }}</span>@endif
                        </label>
                    @empty<p>Nenhuma ideia nesta página para selecionar.</p>@endforelse
                </div>
                <p aria-live="polite"><span x-text="selected.length">0</span> ideia(s) selecionada(s).</p>
            </div>
            <div x-show="confirming" x-cloak class="stack-4">
                <p class="font-semibold">Confirmar a exclusão de <span x-text="selected.length"></span> ideia(s)?</p>
                <p>As ideias marcadas sairão das listagens e não poderão mais ser abertas pelos participantes.</p>
                <label class="block" for="deletion-reason">Motivo da exclusão (mínimo de 10 caracteres)</label>
                <textarea id="deletion-reason" x-ref="reason" class="block w-full rounded-lg" name="deletion_reason" rows="3" minlength="10" maxlength="1000" :required="confirming">{{ old('deletion_reason') }}</textarea>
                <label class="flex items-start gap-3"><input type="checkbox" name="confirm_deletion" value="1" x-model="acknowledged" :required="confirming" :disabled="!confirming">Confirmo a exclusão das ideias selecionadas, preservando o histórico.</label>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" class="apple-button--light" x-show="confirming" @click="confirming = false; acknowledged = false">Voltar à seleção</button>
                <button type="button" class="apple-button--light" @click="$el.closest('dialog').close()">Cancelar</button>
                <button type="submit" class="apple-button" :disabled="selected.length === 0" x-text="confirming ? 'Confirmar exclusão' : 'Continuar'">Continuar</button>
            </div>
        </form>
    </div>
</dialog>
