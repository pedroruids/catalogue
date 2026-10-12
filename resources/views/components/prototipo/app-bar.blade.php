{{--
    Barra de topo (README §AppBar). Ecrãs 3-6.
    Props:
      voltar      string|null  rótulo do botão voltar (ex.: "Artigos"); null → logótipo provisório
      voltarHref  string|null  destino do voltar (omissão: lista de artigos)
      semPrecos   bool         esconde o interruptor de preços (ecrãs 5 e 6)
      semSeleccao bool         esconde o botão Selecção
      sync        string       texto da faixa com rede
      faixa       string       texto da faixa sem rede
    Lê $store.proto: precosVisiveis, total, offline.
--}}
@props([
    'voltar' => null,
    'voltarHref' => null,
    'semPrecos' => false,
    'semSeleccao' => false,
    'sync' => 'Ligado · catálogo sincronizado hoje, 09:12',
    'faixa' => 'Sem rede — a ver a cópia de 08/10/2026, 18:40',
])

<header x-data class="border-b border-border bg-surface">
    <div class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-2.5">
        @if ($voltar)
            <a href="{{ $voltarHref ?? route('prototipo.artigos') }}"
               class="flex h-11 items-center gap-1 rounded-lg pl-1 pr-2.5 text-[15px] font-bold text-brand">
                <x-prototipo.icone nome="chevron-esquerda" class="size-5" />
                <span>{{ $voltar }}</span>
            </a>
        @else
            <x-prototipo.logo />
        @endif

        <div class="flex-1"></div>

        @unless ($semSeleccao)
            <a href="{{ route('prototipo.partilhar') }}" aria-label="Selecção"
               class="flex h-11 items-center gap-1.5 rounded-full border border-border bg-surface px-3 text-[15px] font-bold text-brand tabular-nums">
                <x-prototipo.icone nome="saco" class="size-[18px]" />
                <span x-text="$store.proto.total">0</span>
            </a>
        @endunless

        @unless ($semPrecos)
            <button type="button" x-on:click="$store.proto.togglePrecos()"
                    :aria-pressed="$store.proto.precosVisiveis.toString()"
                    :aria-label="$store.proto.precosVisiveis ? 'Preços visíveis' : 'Preços escondidos'"
                    :title="$store.proto.precosVisiveis ? 'Preços visíveis' : 'Preços escondidos'"
                    aria-pressed="true" aria-label="Preços visíveis"
                    :class="$store.proto.precosVisiveis ? 'border-gold bg-gold text-text' : 'border-brand bg-surface text-brand'"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full border-2">
                <span x-show="$store.proto.precosVisiveis"><x-prototipo.icone nome="olho" class="size-[18px]" /></span>
                <span x-show="! $store.proto.precosVisiveis" x-cloak><x-prototipo.icone nome="olho-cortado" class="size-[18px]" /></span>
            </button>
        @endunless
    </div>

    <div x-show="! $store.proto.offline" class="border-t border-border-soft bg-surface-warm">
        <div class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-1.5 text-[13px] text-muted-2">
            <span class="size-2 shrink-0 rounded-full bg-success"></span>
            <span>{{ $sync }}</span>
        </div>
    </div>
    <div x-show="$store.proto.offline" x-cloak role="status" class="border-t border-warn-border bg-warn-bg">
        <div class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-2 text-sm font-semibold text-text">
            <x-prototipo.icone nome="sem-wifi" class="size-[18px]" />
            <span>{{ $faixa }}</span>
        </div>
    </div>
</header>
