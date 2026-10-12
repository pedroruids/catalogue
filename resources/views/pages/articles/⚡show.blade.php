<?php

use App\Enums\ArticleStatus;
use App\Enums\FieldType;
use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\PriceTier;
use App\Support\PriceVisibility;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.app.layout')] #[Title('Artigo')] class extends Component {
    public Article $article;

    /** Pesquisa/filtro da lista de onde se veio, para o «voltar» a repor (só q e tipo, sempre texto). */
    #[Locked]
    public string $backQuery = '';

    public function togglePrices(): void
    {
        PriceVisibility::toggle();
    }

    public function mount(Article $article): void
    {
        abort_unless($article->status === ArticleStatus::Published, 404);

        $this->article = $article;

        $previous = parse_url(url()->previous());
        if (($previous['path'] ?? null) === parse_url(route('articles.index'), PHP_URL_PATH)) {
            parse_str($previous['query'] ?? '', $params);
            $keep = array_filter(
                ['q' => $params['q'] ?? null, 'tipo' => $params['tipo'] ?? null],
                fn (mixed $v): bool => is_string($v) && $v !== '',
            );
            $this->backQuery = http_build_query($keep);
        }
    }

    public function backUrl(): string
    {
        return route('articles.index').($this->backQuery !== '' ? '?'.$this->backQuery : '');
    }

    /** Artigo com tudo o que a página mostra; escalões só se os preços estiverem visíveis (D5). */
    #[Computed]
    public function loaded(): Article
    {
        $article = $this->article;
        $article->load([
            'productType.fieldDefinitions',
            'images',
            'variants' => fn ($q) => $q->where('is_active', true),
        ]);

        if (PriceVisibility::visible()) {
            $article->variants->load('priceTiers');
        }

        return $article;
    }

    /** Campos do tipo com valor: rótulo => texto. Vazios omitem-se; falso é um valor («Não»). */
    #[Computed]
    public function fields(): array
    {
        $values = $this->loaded->field_values ?? [];
        $rows = [];

        foreach ($this->loaded->productType->fieldDefinitions as $field) {
            $value = $values[$field->key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $rows[$field->name] = match ($field->data_type) {
                FieldType::Boolean => $value ? 'Sim' : 'Não',
                FieldType::Number => is_numeric($value) ? (string) Number::format($value + 0, locale: 'pt_PT') : (string) $value,
                default => (string) $value,
            };
        }

        return $rows;
    }

    /** Eixos com valores, pela ordem em que o artigo os define. */
    #[Computed]
    public function axes(): array
    {
        return collect($this->loaded->variant_axes ?? [])
            ->filter(fn (array $axis): bool => ($axis['name'] ?? '') !== '' && ! empty($axis['values']))
            ->values()
            ->all();
    }

    /**
     * Para o cliente: opções e posição da imagem de cada variante activa. Sem preços (D5).
     *
     * @return list<array{options: array<string, string>, image: int|null}>
     */
    #[Computed]
    public function variantData(): array
    {
        $positions = $this->loaded->images->pluck('id')->flip();

        return $this->loaded->variants->map(fn (ArticleVariant $v): array => [
            'options' => array_map('strval', $v->options),
            'image' => $v->article_image_id !== null ? ($positions[$v->article_image_id] ?? null) : null,
        ])->values()->all();
    }

    /** @return list<array{screen: string, thumb: string}> */
    #[Computed]
    public function imageData(): array
    {
        return $this->loaded->images->map(fn ($i): array => ['screen' => $i->screen_url, 'thumb' => $i->thumb_url])->values()->all();
    }
}; ?>

@php
    $article = $this->loaded;
    $showPrices = \App\Support\PriceVisibility::visible();
    $images = $this->imageData;
    $variantData = $this->variantData;
    $variants = $article->variants;
    $axes = $this->axes;
    $fields = $this->fields;
    $first = $variantData[0] ?? null;
    $initial = [
        'sel' => (object) ($first['options'] ?? []),
        'img' => $first['image'] ?? 0,
    ];
@endphp

<div>
    <x-app.app-bar voltar="Artigos" :voltar-href="$this->backUrl()" :show-prices="$showPrices" />

    <main class="mx-auto max-w-[1240px] px-4 pt-4 pb-12">
        <div x-data="paginaArtigo(@js($initial), @js($variantData), @js($images))" class="flex flex-wrap items-start gap-x-10 gap-y-6">

            {{-- Galeria --}}
            <div class="flex min-w-0 flex-[1_1_440px] flex-col gap-2.5">
                @if (count($images) > 0)
                    <div class="relative aspect-square overflow-hidden rounded-lg bg-img-placeholder">
                        <img x-bind:src="imagens[img]?.screen" src="{{ $images[$first['image'] ?? 0]['screen'] }}" alt="{{ $article->name }}" class="size-full object-cover">
                        @if (count($images) > 1)
                            <span class="absolute right-2.5 bottom-2.5 rounded-full bg-text/75 px-2.5 py-1 text-[13px] font-semibold text-bg tabular-nums"
                                  x-text="`${img + 1} / ${imagens.length}`">{{ ($first['image'] ?? 0) + 1 }} / {{ count($images) }}</span>
                        @endif
                    </div>
                    @if (count($images) > 1)
                        <div class="flex gap-2 overflow-x-auto pb-0.5" role="group" aria-label="Imagens">
                            @foreach ($images as $i => $image)
                                <button type="button" x-on:click="img = {{ $i }}" aria-label="Imagem {{ $i + 1 }} de {{ count($images) }}"
                                        x-bind:aria-current="img === {{ $i }} ? 'true' : null" @if ($i === ($first['image'] ?? 0)) aria-current="true" @endif
                                        x-bind:class="img === {{ $i }} ? 'border-brand' : 'border-border'"
                                        class="size-[60px] shrink-0 overflow-hidden rounded-lg border-2 bg-img-placeholder">
                                    <img src="{{ $image['thumb'] }}" alt="" loading="lazy" class="size-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <x-app.img-placeholder class="rounded-lg" />
                @endif
            </div>

            {{-- Informação --}}
            <div class="flex min-w-0 flex-[1_1_340px] flex-col gap-6">
                <div class="flex flex-col gap-1.5">
                    <span class="text-[13px] font-bold tracking-[0.04em] text-muted uppercase">{{ $article->productType->name }}</span>
                    <h1 class="font-display text-[30px] leading-[1.15] font-semibold text-pretty [overflow-wrap:anywhere]">{{ $article->name }}</h1>
                    <span class="text-sm text-muted tabular-nums">Ref. {{ $article->reference }}</span>
                </div>

                {{-- Variantes: um grupo de opções por eixo --}}
                @if ($variants->isNotEmpty() && $axes !== [])
                    @foreach ($axes as $n => $axis)
                        <div class="flex flex-col gap-2.5" wire:key="eixo-{{ $n }}">
                            <span id="eixo-{{ $n }}" class="text-sm font-bold">{{ $axis['name'] }}: <span class="font-medium" x-text="sel[@js($axis['name'])] ?? ''"></span></span>
                            <div class="flex flex-wrap gap-2" role="group" aria-labelledby="eixo-{{ $n }}">
                                @foreach ($axis['values'] as $value)
                                    <button type="button" x-on:click="escolher(@js($axis['name']), @js($value))"
                                            x-bind:aria-pressed="(sel[@js($axis['name'])] === @js($value)).toString()" aria-pressed="false"
                                            x-bind:class="sel[@js($axis['name'])] === @js($value) ? 'border-brand bg-brand font-bold text-bg' : 'border-border-input bg-surface font-semibold text-text'"
                                            class="min-h-11 max-w-full min-w-[88px] rounded-lg px-3.5 py-2 text-[15px] border-[1.5px] [overflow-wrap:anywhere]">{{ $value }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <p x-show="indice === -1" x-cloak role="status" class="rounded-lg bg-surface-warm p-4 text-[15px] font-semibold text-brand">Esta combinação não está disponível.</p>
                @endif

                {{-- Preço por quantidade (escalões da variante escolhida) --}}
                @if ($variants->isNotEmpty())
                    <section class="flex flex-col gap-2.5" aria-labelledby="titulo-precos">
                        <h2 id="titulo-precos" class="font-display text-xl font-semibold">Preço por quantidade</h2>

                        @if ($showPrices)
                            @foreach ($variants as $i => $variant)
                                <div wire:key="variante-{{ $variant->id }}" x-show="indice === {{ $i }}" @if ($i > 0) x-cloak @endif class="flex flex-col gap-2.5">
                                    @if ($variant->options !== [])
                                        <span class="text-sm text-muted">{{ $variant->optionsLabel() }} · preço unitário</span>
                                    @endif
                                    @if ($variant->priceTiers->isNotEmpty())
                                        <div class="overflow-hidden rounded-lg border border-border bg-surface">
                                            <table class="w-full tabular-nums" data-escaloes>
                                                <thead class="bg-surface-warm text-[13px] font-bold text-muted-2">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-2.5 text-left font-bold">Quantidade mínima</th>
                                                        <th scope="col" class="px-4 py-2.5 text-right font-bold">Preço unitário</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-base">
                                                    @foreach ($variant->priceTiers as $tier)
                                                        <tr class="border-t border-border-soft">
                                                            <td class="px-4 py-3">{{ Number::format($tier->min_quantity, locale: 'pt_PT') }} un.</td>
                                                            <td class="px-4 py-3 text-right font-bold text-brand">{{ PriceTier::formatCents($tier->unit_price_cents) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="flex flex-col gap-1 rounded-lg border border-border bg-surface p-4">
                                            <span class="text-[17px] font-bold text-brand">Preço sob consulta</span>
                                            <span class="text-sm text-muted">Esta variante ainda não tem escalões de preço.</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="flex items-center gap-3 rounded-lg bg-surface-warm p-4 text-brand">
                                <x-app.icone nome="olho-cortado" class="size-[22px]" />
                                <span class="text-[15px] font-semibold">Preços escondidos</span>
                            </div>
                        @endif
                    </section>
                @endif

                {{-- Características --}}
                @if ($fields !== [])
                    <section class="flex flex-col gap-2.5" aria-labelledby="titulo-caracteristicas">
                        <h2 id="titulo-caracteristicas" class="font-display text-xl font-semibold">Características</h2>
                        <dl class="border-t border-border">
                            @foreach ($fields as $label => $value)
                                <div class="grid grid-cols-[minmax(110px,40%)_1fr] gap-3 border-b border-border py-[11px] text-[15px] leading-[1.45]">
                                    <dt class="min-w-0 text-muted [overflow-wrap:anywhere]">{{ $label }}</dt>
                                    <dd class="min-w-0 text-pretty text-text [overflow-wrap:anywhere]">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif
            </div>
        </div>
    </main>

</div>
