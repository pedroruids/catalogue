{{--
    Barra de topo da app do vendedor.
    Props:
      voltar      string|null  rótulo do botão voltar (ex.: "Artigos"); null → logótipo
      voltarHref  string|null  destino do voltar (omissão: lista de artigos)
      showPrices  bool|null    estado do interruptor de preços; null → sem interruptor
    O interruptor chama `togglePrices` na página Livewire que contém a barra (trait `TogglesPrices`).
--}}
@props(['voltar' => null, 'voltarHref' => null, 'showPrices' => null])

<header class="border-b border-border bg-surface">
    <div class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-2.5">
        @if ($voltar)
            <a href="{{ $voltarHref ?? route('articles.index') }}"
               class="flex h-11 items-center gap-1 rounded-lg pl-1 pr-2.5 text-[15px] font-bold text-brand">
                <x-app.icone nome="chevron-esquerda" class="size-5" />
                <span>{{ $voltar }}</span>
            </a>
        @else
            <a href="{{ route('articles.index') }}" aria-label="Maravilha, lista de artigos"><x-app.logo /></a>
        @endif

        <div class="flex-1"></div>

        @if ($showPrices !== null)
            <button type="button" wire:click="togglePrices"
                    aria-pressed="{{ $showPrices ? 'true' : 'false' }}"
                    aria-label="{{ $showPrices ? 'Preços visíveis' : 'Preços escondidos' }}"
                    title="{{ $showPrices ? 'Preços visíveis' : 'Preços escondidos' }}"
                    @class([
                        'flex size-11 shrink-0 items-center justify-center rounded-full border-2',
                        'border-gold bg-gold text-text' => $showPrices,
                        'border-brand bg-surface text-brand' => ! $showPrices,
                    ])>
                <x-app.icone :nome="$showPrices ? 'olho' : 'olho-cortado'" class="size-[18px]" />
            </button>
        @endif

        @if (auth()->user()?->isAdmin())
            <a href="/admin" aria-label="Backoffice" title="Backoffice"
               class="flex size-11 shrink-0 items-center justify-center rounded-full border border-border bg-surface text-brand">
                <x-app.icone nome="engrenagem" class="size-[18px]" />
            </a>
        @endif

        <a href="{{ route('profile.edit') }}" aria-label="A minha conta" title="A minha conta"
           class="flex size-11 shrink-0 items-center justify-center rounded-full border border-border bg-surface text-brand">
            <x-app.icone nome="utilizador" class="size-[18px]" />
        </a>
    </div>
</header>
