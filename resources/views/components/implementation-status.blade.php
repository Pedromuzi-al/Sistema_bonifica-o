@props(['snapshot' => null, 'status' => null])
@php($implementationStatus = \App\Support\ImplementationStatus::fromSnapshot($snapshot ?? ['implementation_status' => $status]))
<div class="rounded-xl border border-slate-200 bg-slate-50 p-4" data-implementation-status="{{ $implementationStatus }}">
    <p class="text-sm text-slate-600">Situação da implementação</p>
    <p class="font-semibold">{{ \App\Support\ImplementationStatus::LABELS[$implementationStatus] }}</p>
    @if($snapshot)<p class="mt-1 text-xs text-slate-500">Situação registrada na abertura deste ciclo.</p>@endif
</div>
