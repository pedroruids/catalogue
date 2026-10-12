{{--
    Cartão da lista (ecrã 3). Imagem 1:1, nome (2 linhas), ref, «desde X €», botão Juntar.
    Props: artigo (array de PrototipoDados::artigos()) · href (omissão: página do artigo)
    O preço só aparece se o artigo trouxer 'desde' (o servidor pode retirá-lo) E $store.proto.precosVisiveis.
    ⚠ Protótipo: o esconder é x-show; na implementação real o servidor não envia o preço (D5).
    Botão: «+ Juntar» ↔ «✓ Na selecção» ($store.proto.toggleSeleccao); sem rede → «Precisa de rede».
    O cartão é um <article> com ligação esticada no nome (toque no cartão, fora do botão → artigo).
--}}
@props(['artigo', 'href' => null])

@php
    $href ??= route('prototipo.artigo', $artigo['ref']);
    $ref = $artigo['ref'];
@endphp

<article x-data {{ $attributes->class('relative flex min-w-0 flex-col overflow-hidden rounded-lg border border-border bg-surface') }}>
    <x-prototipo.img-placeholder :etiqueta="$artigo['imagens'] ? mb_strtolower($artigo['nome']) : null" :sem-imagens="! $artigo['imagens']" />
    <div class="flex flex-1 flex-col gap-1 px-3 pb-3 pt-2.5">
        <h2 class="line-clamp-2 text-[15px] leading-[1.3] font-bold text-pretty">
            <a href="{{ $href }}" class="after:absolute after:inset-0 after:content-['']">{{ $artigo['nome'] }}</a>
        </h2>
        <span class="text-[13px] text-muted tabular-nums">{{ $ref }}</span>
        @if ($artigo['desde'] !== null)
            <span x-show="$store.proto.precosVisiveis" class="text-sm">desde <strong class="font-bold text-brand tabular-nums">{{ \App\Support\PrototipoDados::euros($artigo['desde']) }}</strong></span>
        @endif
        <div class="min-h-1.5 flex-1"></div>

        <button type="button" x-show="! $store.proto.offline" x-on:click="$store.proto.toggleSeleccao(@js($ref))"
                :aria-pressed="$store.proto.naSeleccao(@js($ref)).toString()"
                :class="$store.proto.naSeleccao(@js($ref)) ? 'bg-brand text-bg' : 'bg-surface text-brand hover:bg-surface-warm'"
                class="relative z-10 flex h-11 items-center justify-center gap-1.5 rounded-lg border-[1.5px] border-brand text-sm font-bold">
            <span x-show="! $store.proto.naSeleccao(@js($ref))" class="flex items-center gap-1.5"><x-prototipo.icone nome="mais" class="size-4" /> Juntar</span>
            <span x-show="$store.proto.naSeleccao(@js($ref))" x-cloak class="flex items-center gap-1.5"><x-prototipo.icone nome="visto" class="size-4" /> Na selecção</span>
        </button>
        <button type="button" x-show="$store.proto.offline" x-cloak disabled title="Juntar precisa de rede"
                class="relative z-10 flex h-11 cursor-not-allowed items-center justify-center rounded-lg border-[1.5px] border-disabled bg-disabled text-[13px] font-bold text-disabled-text">Precisa de rede</button>
    </div>
</article>
