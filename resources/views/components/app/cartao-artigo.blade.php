{{--
    Cartão da lista de artigos: imagem reduzida 1:1, nome (2 linhas), referência e preço «desde».
    Props: article (com primaryImage e variants.priceTiers carregados) · showPrices · selected
    O botão chama `toggleSelection(id)` na página Livewire que contém o cartão.
    Com preços escondidos (D5) nem «desde» nem «Preço sob consulta» chegam ao HTML.
    O cartão é um <article> com a ligação esticada no nome.
--}}
@props(['article', 'showPrices' => true, 'selected' => false])

@php
    $image = $article->primaryImage;
    $fromCents = $showPrices ? $article->fromPriceCents() : null;
@endphp

<article {{ $attributes->class('relative flex min-w-0 flex-col overflow-hidden rounded-lg border border-border bg-surface') }}>
    @if ($image)
        <img src="{{ $image->thumb_url }}" alt="" loading="lazy" class="aspect-square w-full bg-img-placeholder object-cover">
    @else
        <x-app.img-placeholder />
    @endif
    <div class="flex flex-1 flex-col gap-1 px-3 pt-2.5 pb-3">
        <h2 class="line-clamp-2 text-[15px] leading-[1.3] font-bold text-pretty">
            <a href="{{ route('articles.show', $article) }}" class="after:absolute after:inset-0 after:content-['']">{{ $article->name }}</a>
        </h2>
        <span class="text-[13px] text-muted tabular-nums">{{ $article->reference }}</span>
        @if ($showPrices)
            @if ($fromCents !== null)
                <span class="text-sm">desde <strong class="font-bold text-brand tabular-nums">{{ \App\Models\PriceTier::formatCents($fromCents) }}</strong></span>
            @else
                <span class="text-sm text-muted">Preço sob consulta</span>
            @endif
        @endif
        <div class="min-h-1.5 flex-1"></div>
        <button type="button" wire:click="toggleSelection({{ $article->id }})" aria-pressed="{{ $selected ? 'true' : 'false' }}"
                @class([
                    'relative z-10 flex h-11 items-center justify-center gap-1.5 rounded-lg border-[1.5px] border-brand text-sm font-bold',
                    'bg-brand text-bg' => $selected,
                    'bg-surface text-brand hover:bg-surface-warm' => ! $selected,
                ])>
            @if ($selected)
                <x-app.icone nome="visto" class="size-4" /> Na selecção
            @else
                <x-app.icone nome="mais" class="size-4" /> Juntar
            @endif
        </button>
    </div>
</article>
