{{--
    Espaço reservado para foto de produto: riscas a 135° + etiqueta «foto · …».
    Props: etiqueta (string, texto depois de «foto · »; null → só «foto»)
           · ratio ('1/1' | '4/3' | null para tamanho livre por classe)
           · semImagens (bool: variante «Sem imagens», fundo liso + ícone)
--}}
@props(['etiqueta' => null, 'ratio' => '1/1', 'semImagens' => false])

<div {{ $attributes->class([
    'flex items-center justify-center p-2 text-center',
    'aspect-square' => $ratio === '1/1',
    'aspect-[4/3]' => $ratio === '4/3',
    'bg-riscas' => ! $semImagens,
    'flex-col gap-2 bg-skeleton-2' => $semImagens,
]) }}>
    @if ($semImagens)
        <x-prototipo.icone nome="foto" class="size-7 text-img-label" />
        <span class="text-[13px] font-semibold text-img-label">Sem imagens</span>
    @else
        <span class="text-xs text-img-label">foto{{ $etiqueta ? ' · '.$etiqueta : '' }}</span>
    @endif
</div>
