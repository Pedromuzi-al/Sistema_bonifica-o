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
                        <select name="beneficiary_sectors[]" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" size="5">
                            @foreach ($sectors as $sector)
                                <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                            @endforeach
                        </select>
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
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Salvar ideia</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
