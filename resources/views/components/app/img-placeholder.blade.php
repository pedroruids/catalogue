{{-- Artigo sem imagens: fundo liso 1:1 com ícone e «Sem imagens». --}}
<div {{ $attributes->class('flex aspect-square flex-col items-center justify-center gap-2 bg-skeleton-2 p-2 text-center') }}>
    <x-app.icone nome="foto" class="size-7 text-img-label" />
    <span class="text-[13px] font-semibold text-img-label">Sem imagens</span>
</div>
