{{--
    Estado vazio de ecrã inteiro: ícone em círculo 64 px, título, texto e acção (slot).
    Props: icone (nome do <x-app.icone>) · titulo · texto
--}}
@props(['icone' => 'caixa', 'titulo', 'texto' => null])

<div {{ $attributes->class('flex flex-col items-center gap-2.5 px-4 py-12 text-center') }}>
    <div class="flex size-16 items-center justify-center rounded-full bg-surface-warm text-brand">
        <x-app.icone :nome="$icone" class="size-7" />
    </div>
    <h2 class="font-display text-[22px] font-semibold">{{ $titulo }}</h2>
    @if ($texto)
        <p class="max-w-[360px] text-[15px] leading-normal text-pretty text-muted">{{ $texto }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-1.5 flex flex-col items-center gap-2.5">{{ $slot }}</div>
    @endif
</div>
