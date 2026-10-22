<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Seus avisos</h2><p class="mt-2 text-slate-600">Acompanhe o andamento das suas ideias e os avisos de votação. Esta área é privada.</p></x-slot>
    <div class="mx-auto max-w-3xl stack-5 px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3"><p>{{ $unreadCount }} {{ $unreadCount === 1 ? 'aviso não lido' : 'avisos não lidos' }}</p><a class="text-sky-700 underline" href="{{ request()->boolean('unread') ? route('notifications.index') : route('notifications.index', ['unread'=>1]) }}">{{ request()->boolean('unread') ? 'Mostrar todos' : 'Mostrar somente os não lidos' }}</a></div>
        @if ($unreadCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="apple-button--light">Marcar todos como lidos</button></form>@endif
        @forelse ($notifications as $notification)
            <article class="apple-card stack-3 p-6"><div class="flex flex-wrap justify-between gap-2"><h3 class="font-bold">{{ $notification->title }}</h3><span class="text-sm text-slate-500">{{ $notification->created_at->format('d/m/Y H:i') }}</span></div><p class="whitespace-pre-line">{{ $notification->message }}</p>
                @if (!$notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf<button class="text-sm text-sky-700 underline">Marcar como lido</button></form>@else<p class="text-sm text-slate-500">Lido</p>@endif
            </article>
        @empty<section class="apple-card p-6"><h3 class="font-bold">Você está em dia!</h3><p class="mt-2">Quando houver novidades, seus avisos aparecerão aqui.</p></section>@endforelse
        {{ $notifications->links() }}
        <a class="text-sm text-sky-700 underline" href="{{ route('dashboard') }}">Voltar para o início</a>
    </div>
</x-app-layout>
