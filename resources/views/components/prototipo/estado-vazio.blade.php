{{--
    Estado vazio / erro de ecrã inteiro: ícone em círculo 64 px, título, texto e acção.
    Props: icone (nome do <x-prototipo.icone>) · titulo · texto · tom ('neutro' | 'erro' | 'sucesso') · espaco (classes de padding vertical, omissão py-12)
    Slots: default (acções: botões/ligações, por baixo do texto) · texto pode vir também por slot `corpo`.
--}}
@props(['icone' => 'caixa', 'titulo', 'texto' => null, 'tom' => 'neutro', 'espaco' => 'py-12'])

<div {{ $attributes->class(['flex flex-col items-center gap-2.5 px-4 text-center', $espaco]) }}>
    <div @class([
        'flex size-16 items-center justify-center rounded-full',
        'bg-surface-warm text-brand' => $tom === 'neutro',
        'bg-error-bg text-error' => $tom === 'erro',
        'bg-success-bg text-success' => $tom === 'sucesso',
    ])>
        <x-prototipo.icone :nome="$icone" class="size-7" />
    </div>
    <h2 class="font-display text-[22px] font-semibold">{{ $titulo }}</h2>
    @if ($texto || isset($corpo))
        <p class="max-w-[360px] text-[15px] leading-normal text-pretty text-muted">{{ $corpo ?? $texto }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-1.5 flex flex-col items-center gap-2.5">{{ $slot }}</div>
    @endif
</div>
