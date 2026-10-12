{{--
    Página pública da partilha (copiada do protótipo publica.blade.php, ecrã 7).
    Variáveis: $share · $seller (só o nome) · $articles (publicados, com images e variants activas; escalões só em com-preços)
    · $withPrices · $imagesOnly.
    Regra D5: os preços saem do servidor só em com-preços — nenhum «€» nem valor de escalão fora do @if ($withPrices).
--}}
@php
    $total = $articles->count();
    $axesOf = function ($article): array {
        $out = [];
        foreach ($article->variants as $variant) {
            foreach ($variant->options as $axis => $value) {
                $out[$axis][(string) $value] = true;
            }
        }

        return array_map(fn (array $values): string => implode(', ', array_keys($values)), $out);
    };
@endphp
<x-publica.layout titulo="Veja os artigos">
    <main class="mx-auto flex w-full max-w-[1240px] flex-col gap-6 px-4 pb-12 pt-6">
        @if ($total === 0)
            <div class="flex flex-col items-center gap-2.5 px-4 py-16 text-center">
                <div class="flex size-16 items-center justify-center rounded-full bg-surface-warm text-brand">
                    <x-app.icone nome="corrente" class="size-7" />
                </div>
                <h1 class="font-display text-2xl font-semibold text-pretty">Estes artigos já não estão disponíveis</h1>
                <p class="max-w-[360px] text-[15px] leading-normal text-pretty text-muted">Saíram do catálogo depois de serem partilhados. Fale com a Maravilha para ver alternativas.</p>
            </div>
        @else
            <div class="flex flex-col gap-2">
                <h1 class="font-display text-[30px] leading-[1.15] font-semibold">Veja os artigos</h1>
                <p class="text-[15px] leading-normal text-muted-2">Selecção partilhada por <strong class="text-text">{{ $seller }}</strong>, da Maravilha.</p>
                <div class="mt-1 flex flex-wrap gap-2">
                    <span class="flex h-[30px] items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums">{{ $total === 1 ? '1 artigo' : $total.' artigos' }}</span>
                    <span class="flex h-[30px] items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums">Disponível até {{ $share->expires_at->timezone(config('app.timezone'))->format('d/m/Y') }}</span>
                </div>
            </div>

            @if ($imagesOnly)
                {{-- Só imagens: até 2 por artigo, com o nome (D11) --}}
                <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-3">
                    @foreach ($articles as $article)
                        @forelse ($article->images->take(2) as $image)
                            <figure class="flex min-w-0 flex-col gap-1.5">
                                <img src="{{ $image->screen_url }}" alt="" loading="lazy" class="aspect-square w-full rounded-lg bg-img-placeholder object-cover">
                                <figcaption class="text-sm leading-[1.3] font-semibold text-pretty">{{ $article->name }}</figcaption>
                            </figure>
                        @empty
                            <figure class="flex min-w-0 flex-col gap-1.5">
                                <x-app.img-placeholder class="rounded-lg" />
                                <figcaption class="text-sm leading-[1.3] font-semibold text-pretty">{{ $article->name }}</figcaption>
                            </figure>
                        @endforelse
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-4">
                    @foreach ($articles as $article)
                        @php $image = $article->images->firstWhere('is_primary', true) ?? $article->images->first(); @endphp
                        <article class="flex min-w-0 flex-col overflow-hidden rounded-lg border border-border bg-surface">
                            @if ($image)
                                <img src="{{ $image->screen_url }}" alt="{{ $article->name }}" loading="lazy" class="aspect-[4/3] w-full bg-img-placeholder object-cover">
                            @else
                                <x-app.img-placeholder class="aspect-[4/3]" />
                            @endif
                            <div class="flex flex-col gap-3 p-4">
                                <div class="flex flex-col gap-0.5">
                                    <h2 class="font-display text-xl leading-[1.2] font-semibold text-pretty">{{ $article->name }}</h2>
                                    <span class="text-[13px] text-muted">Ref. {{ $article->reference }}</span>
                                </div>

                                @foreach ($axesOf($article) as $axis => $values)
                                    <span class="text-sm leading-[1.45] text-muted-2">{{ $axis }}: {{ $values }}</span>
                                @endforeach

                                @if ($withPrices)
                                    @forelse ($article->variants->filter(fn ($v) => $v->priceTiers->isNotEmpty()) as $variant)
                                        <div class="flex flex-col gap-1.5">
                                            <span class="text-[13px] font-bold text-muted-2">Preço unitário{{ $variant->options !== [] ? ' · '.collect($variant->options)->implode(', ') : '' }}</span>
                                            <div class="overflow-hidden rounded-lg border border-border-soft">
                                                @foreach ($variant->priceTiers as $tier)
                                                    <div class="flex justify-between gap-3 border-b border-border-soft px-3 py-2 text-[15px] tabular-nums last:border-b-0">
                                                        <span>desde {{ \Illuminate\Support\Number::format($tier->min_quantity, locale: 'pt_PT') }} un.</span>
                                                        <strong class="text-brand">{{ \App\Models\PriceTier::formatCents($tier->unit_price_cents) }}</strong>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-sm font-semibold">Preço sob consulta</span>
                                    @endforelse
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </main>
</x-publica.layout>
