<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar nova ideia</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('ideas.store') }}" method="POST" class="bg-white shadow rounded-xl p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Título</label>
                        <input name="title" value="{{ old('title') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Problema identificado</label>
                        <textarea name="problem_description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('problem_description') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Solução proposta</label>
                        <textarea name="solution_description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('solution_description') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Setor de origem</label>
                        <select name="origin_sector_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Selecione</option>
                            @foreach ($sectors as $sector)
                                <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Setores beneficiados</label>
                        <div
                            class="relative mt-1"
                            x-data="{
                                open: false,
                                selected: @js(collect(old('beneficiary_sectors', []))->map(fn ($id) => (string) $id)->values()),
                                allSectorIds: @js($sectors->pluck('id')->map(fn ($id) => (string) $id)->values()),
                                get allSelected() {
                                    return this.allSectorIds.length > 0 && this.allSectorIds.every(id => this.selected.includes(id));
                                },
                                toggleAll(checked) {
                                    this.selected = checked ? [...this.allSectorIds] : [];
                                }
                            }"
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-slate-700 shadow-sm transition focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                x-bind:aria-expanded="open.toString()"
                                aria-controls="beneficiary-sector-options"
                                @click="open = !open"
                            >
                                <span
                                    class="truncate"
                                    x-text="selected.length === 0 ? 'Selecione os setores' : (allSelected ? 'Todos os setores' : (selected.length === 1 ? '1 setor selecionado' : selected.length + ' setores selecionados'))"
                                ></span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform" x-bind:class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition.origin.top
                                class="absolute left-0 right-0 z-20 mt-2 max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10"
                                id="beneficiary-sector-options"
                                role="group"
                                aria-label="Setores beneficiados"
                            >
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-sky-50">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                        x-bind:checked="allSelected"
                                        @change="toggleAll($event.target.checked)"
                                    >
                                    Todos
                                </label>

                                <div class="my-1 border-t border-slate-100"></div>

                                @foreach ($sectors as $sector)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                        <input
                                            type="checkbox"
                                            name="beneficiary_sectors[]"
                                            value="{{ $sector->id }}"
                                            class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                            x-model="selected"
                                            @checked(in_array((string) $sector->id, collect(old('beneficiary_sectors', []))->map(fn ($id) => (string) $id)->all(), true))
                                        >
                                        {{ $sector->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @error('beneficiary_sectors')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('beneficiary_sectors.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Resultados esperados</label>
                        <textarea name="expected_results" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('expected_results') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Recursos necessários</label>
                        <textarea name="resources_needed" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('resources_needed') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Informações complementares</label>
                        <textarea name="additional_information" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('additional_information') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('ideas.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</a>
                    <button type="submit" class="rounded-full bg-[#0faddf] px-5 py-2.5 text-sm font-semibold text-slate-950 shadow-sm shadow-sky-500/20 hover:bg-[#0b96c2]">Salvar ideia</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
