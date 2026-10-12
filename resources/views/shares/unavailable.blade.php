{{-- Ligação que não abre artigos: inexistente (404), expirada ou revogada (410). Sem dados de artigo nem de vendedor. --}}
@php
    [$titulo, $texto] = match ($reason) {
        'expired' => ['Esta ligação expirou', 'Peça a quem lha enviou uma ligação nova.'],
        'revoked' => ['Esta ligação já não está activa', 'Quem a partilhou desactivou-a. Se precisar, peça uma ligação nova.'],
        default => ['Não encontrámos esta ligação', 'Confirme se a copiou completa, até ao fim.'],
    };
@endphp
<x-publica.layout :titulo="$titulo">
    <main class="mx-auto flex w-full max-w-[1240px] flex-col px-4 pb-12 pt-6">
        <div class="flex flex-col items-center gap-2.5 px-4 py-16 text-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-surface-warm text-brand">
                <x-app.icone nome="corrente" class="size-7" />
            </div>
            <h1 class="font-display text-2xl font-semibold text-pretty">{{ $titulo }}</h1>
            <p class="max-w-[360px] text-[15px] leading-normal text-pretty text-muted">{{ $texto }}</p>
        </div>
    </main>
</x-publica.layout>
