<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ProductType;
use App\Support\PriceVisibility;
use App\Support\Selection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('components.app.layout')] #[Title('Artigos')] class extends Component {
    private const PAGE_SIZE = 24;

    /** Pesquisa por nome ou referência. Na query string para o «voltar» do browser repor a lista. */
    #[Url(as: 'q', history: true)]
    public string $search = '';

    /** Id do tipo de produto (string: um valor forjado na URL não pode rebentar a hidratação). */
    #[Url(as: 'tipo', history: true)]
    public string $type = '';

    public int $perPage = self::PAGE_SIZE;

    public function togglePrices(): void
    {
        PriceVisibility::toggle();
    }

    public function toggleSelection(int $id): void
    {
        Selection::toggle($id);
    }

    public function updatedSearch(): void
    {
        $this->perPage = self::PAGE_SIZE;
    }

    public function selectType(string $id): void
    {
        $this->type = $id;
        $this->perPage = self::PAGE_SIZE;
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type');
        $this->perPage = self::PAGE_SIZE;
    }

    public function loadMore(): void
    {
        $this->perPage += self::PAGE_SIZE;
    }

    /** @return Builder<Article> */
    private function filtered(): Builder
    {
        $term = trim($this->search);

        return Article::query()
            ->where('status', ArticleStatus::Published)
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '\\%_').'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('reference', 'like', $like));
            })
            ->when(ctype_digit($this->type), fn (Builder $query) => $query->where('product_type_id', (int) $this->type));
    }

    #[Computed]
    public function total(): int
    {
        return $this->filtered()->count();
    }

    /** @return Collection<int, Article> */
    #[Computed]
    public function articles(): Collection
    {
        $query = $this->filtered()->with('primaryImage')->orderBy('name')->orderBy('id')->limit($this->perPage);

        // Sem preços não se vão buscar variantes nem escalões (D5).
        if (PriceVisibility::visible()) {
            $query->with(['variants' => fn ($q) => $q->where('is_active', true)->with('priceTiers')]);
        }

        return $query->get();
    }

    /** Só os tipos que têm artigos publicados. */
    #[Computed]
    public function types(): Collection
    {
        return ProductType::query()
            ->whereHas('articles', fn (Builder $q) => $q->where('status', ArticleStatus::Published))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function hasFilters(): bool
    {
        return trim($this->search) !== '' || ctype_digit($this->type);
    }

    public function typeLabel(): ?string
    {
        return ctype_digit($this->type) ? $this->types->firstWhere('id', (int) $this->type)?->name : null;
    }
}; ?>

@php
    $showPrices = \App\Support\PriceVisibility::visible();
    $total = $this->total;
    $chip = 'flex h-11 shrink-0 items-center gap-1.5 rounded-full border text-sm font-semibold whitespace-nowrap';
@endphp

<div>
    <x-app.app-bar :show-prices="$showPrices" />

    <main class="mx-auto flex max-w-[1240px] flex-col gap-4 px-4 pt-5 pb-10">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h1 class="font-display text-[28px] font-semibold">Artigos</h1>
            <span class="text-sm text-muted tabular-nums" aria-live="polite">{{ $total === 1 ? '1 artigo' : $total.' artigos' }}</span>
        </div>

        <x-app.catalogo-offline />

        {{-- Pesquisa --}}
        <div class="relative">
            <x-app.icone nome="lupa" class="pointer-events-none absolute top-3.5 left-3.5 size-5 text-muted" />
            <label for="pesquisa" class="sr-only">Pesquisar artigos</label>
            <input id="pesquisa" type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar por nome ou referência"
                   autocomplete="off"
                   class="h-12 w-full rounded-lg border-[1.5px] border-border-input bg-surface pr-3.5 pl-11 text-base text-text placeholder:text-muted focus:border-brand">
        </div>

        {{-- Filtro por tipo de produto --}}
        @if ($this->types->isNotEmpty())
            <div x-data="{ open: false }">
                <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1" role="group" aria-label="Filtros">
                    @if ($this->typeLabel() !== null)
                        <button type="button" wire:click="selectType('')" aria-label="Retirar filtro Tipo: {{ $this->typeLabel() }}"
                                class="{{ $chip }} border-brand bg-brand pr-2.5 pl-3.5 text-bg">
                            <span>Tipo: {{ $this->typeLabel() }}</span>
                            <x-app.icone nome="fechar" class="size-4" />
                        </button>
                    @else
                        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-controls="opcoes-tipo"
                                class="{{ $chip }} border-border-input bg-surface pr-3 pl-3.5 text-text hover:bg-surface-warm">
                            Tipo de produto
                            <x-app.icone nome="chevron-baixo" class="size-4" />
                        </button>
                    @endif
                </div>

                {{-- Opções por baixo da linha (um menu flutuante ficaria cortado pelo overflow da linha de chips) --}}
                <div id="opcoes-tipo" x-show="open" x-cloak class="mt-2 flex flex-wrap gap-2 rounded-lg border border-border bg-surface p-3">
                    @foreach ($this->types as $option)
                        <button type="button" wire:key="tipo-{{ $option->id }}" wire:click="selectType('{{ $option->id }}')" x-on:click="open = false"
                                class="{{ $chip }} border-border-input bg-surface px-3.5 text-text hover:bg-surface-warm">{{ $option->name }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($total > 0)
            @php($articles = $this->articles)
            @php($selected = \App\Support\Selection::ids())
            <div class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">
                @foreach ($articles as $article)
                    <x-app.cartao-artigo wire:key="artigo-{{ $article->id }}" :article="$article" :show-prices="$showPrices" :selected="in_array($article->id, $selected, true)" />
                @endforeach
            </div>

            @if ($total > $perPage)
                <button type="button" wire:click="loadMore"
                        class="mx-auto flex h-12 items-center justify-center rounded-lg border-[1.5px] border-brand bg-surface px-5 text-base font-bold text-brand hover:bg-surface-warm">Mostrar mais artigos</button>
            @endif
        @elseif ($this->hasFilters())
            <x-app.estado-vazio icone="lupa" titulo="Nenhum artigo encontrado"
                texto="Nada corresponde a esta pesquisa com estes filtros. Experimente outra palavra ou retire filtros.">
                <button type="button" wire:click="clearFilters"
                        class="flex h-12 items-center justify-center rounded-lg border-[1.5px] border-brand bg-surface px-5 text-base font-bold text-brand hover:bg-surface-warm">Limpar filtros</button>
            </x-app.estado-vazio>
        @else
            <x-app.estado-vazio icone="caixa" titulo="Ainda não há artigos publicados"
                texto="Quando o admin publicar artigos no backoffice, aparecem aqui." />
        @endif
    </main>
</div>
