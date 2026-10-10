{{--
    Ecrã 3: Lista de artigos (docs/design_handoff_maravilha_app/README.md §3 · ListaB.dc.html).
    Variáveis: $estado, $estados, $artigos.
    Estados: cheio · a-carregar · vazio · vazio-filtro · erro · cheio-sem-rede.
    Pesquisa e filtros no cliente (Alpine, componente `listaArtigos` abaixo).
    ⚠ Protótipo: os preços escondem-se com x-show ($store.proto.precosVisiveis). Na implementação
    real é o servidor que não os envia (D5).
    ⚠ Eixos e opções dos filtros por confirmar (PRD §11); as faixas de preço são de exemplo.
--}}
@php
    $semArtigos = $estado === 'vazio';
    $semFiltros = in_array($estado, ['vazio', 'erro'], true);
    $mostraGrelha = in_array($estado, ['cheio', 'vazio-filtro', 'cheio-sem-rede'], true);
    $semRede = in_array($estado, ['erro', 'cheio-sem-rede'], true);

    $lista = $semArtigos ? [] : $artigos;

    $normalizar = fn (string $s): string => mb_strtolower(\Illuminate\Support\Str::ascii($s));
    $indice = array_map(fn (array $a): array => [
        'ref' => $a['ref'],
        'texto' => $normalizar($a['nome'].' '.$a['ref']),
        'categoria' => $a['categoria'],
        'tipo' => $a['tipo'],
        'cores' => array_column($a['cores'], 'nome'),
        'desde' => $a['desde'],
    ], $lista);

    $opcoes = [
        'categoria' => array_values(array_unique(array_column($lista, 'categoria'))),
        'tipo' => array_values(array_unique(array_column($lista, 'tipo'))),
        'cor' => array_values(array_unique(array_merge([], ...array_map(fn (array $a): array => array_column($a['cores'], 'nome'), $lista)))),
        'preco' => ['até 1 €', '1 € a 5 €', 'mais de 5 €'],
    ];

    // Estado «vazio-filtro»: a pesquisa e os filtros do mockup já aplicados.
    $inicial = $estado === 'vazio-filtro'
        ? ['q' => 'lanyard', 'filtros' => ['categoria' => null, 'tipo' => 'Têxtil', 'cor' => 'Dourado', 'preco' => null]]
        : ['q' => '', 'filtros' => ['categoria' => null, 'tipo' => null, 'cor' => null, 'preco' => null]];

    $chips = [
        'categoria' => ['rotulo' => 'Categoria', 'curto' => 'Categoria'],
        'tipo' => ['rotulo' => 'Tipo de produto', 'curto' => 'Tipo'],
        'cor' => ['rotulo' => 'Cor', 'curto' => 'Cor'],
        'preco' => ['rotulo' => 'Preço', 'curto' => 'Preço'],
    ];

    $chipBase = 'flex h-11 shrink-0 items-center gap-1.5 rounded-full border text-sm font-semibold whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-60';
@endphp

<x-prototipo.layout titulo="Artigos" :estados="$estados" :estado="$estado"
    :app-bar="$estado === 'erro' ? ['faixa' => 'Sem rede e sem catálogo descarregado'] : []">
    <main x-data="listaArtigos(@js($indice), @js($inicial), @js($opcoes))"
          @if ($semRede) x-init="$store.proto.forcarOffline()" @endif
          class="mx-auto flex max-w-[1240px] flex-col gap-4 px-4 pt-5 pb-10">

        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h1 class="font-display text-[28px] font-semibold">Artigos</h1>
            @if ($mostraGrelha)
                <span class="text-sm text-muted tabular-nums" aria-live="polite"
                      x-text="contagem">{{ $estado === 'vazio-filtro' ? '0 artigos' : count($lista).' artigos' }}</span>
            @endif
        </div>

        {{-- Pesquisa --}}
        <div class="relative">
            <x-prototipo.icone nome="lupa" class="pointer-events-none absolute top-3.5 left-3.5 size-5 text-muted" />
            <label for="pesquisa" class="sr-only">Pesquisar artigos</label>
            <input id="pesquisa" type="search" x-model="q" placeholder="Pesquisar por nome ou referência"
                   value="{{ $inicial['q'] }}" @disabled($semFiltros) autocomplete="off"
                   class="h-12 w-full rounded-lg border-[1.5px] border-border-input bg-surface pr-3.5 pl-11 text-base text-text placeholder:text-muted focus:border-brand disabled:cursor-not-allowed disabled:bg-disabled">
        </div>

        {{-- Filtros: linha de chips com scroll próprio, a sangrar até às margens --}}
        <div>
            <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1" role="group" aria-label="Filtros" data-chips>
                @foreach ($chips as $chave => $chip)
                    {{-- O chip «Preço» desaparece com os preços escondidos (D11) --}}
                    <div class="flex shrink-0" @if ($chave === 'preco') x-show="$store.proto.precosVisiveis" @endif>
                        <button type="button" x-show="! filtros.{{ $chave }}" x-on:click="abrir('{{ $chave }}')"
                                :aria-expanded="(aberto === '{{ $chave }}').toString()" aria-expanded="false" aria-controls="opcoes-filtro"
                                @disabled($semFiltros)
                                @if ($inicial['filtros'][$chave]) x-cloak @endif
                                @class([$chipBase, 'border-border-input bg-surface pr-3 pl-3.5 text-text enabled:hover:bg-surface-warm'])>
                            {{ $chip['rotulo'] }}
                            <x-prototipo.icone nome="chevron-baixo" class="size-4" />
                        </button>
                        <button type="button" x-show="filtros.{{ $chave }}" x-on:click="retirar('{{ $chave }}')"
                                :aria-label="'Retirar filtro {{ $chip['curto'] }}: ' + filtros.{{ $chave }}"
                                @unless ($inicial['filtros'][$chave]) x-cloak @endunless
                                @class([$chipBase, 'border-brand bg-brand pr-2.5 pl-3.5 text-bg'])>
                            <span>{{ $chip['curto'] }}: <span x-text="filtros.{{ $chave }}">{{ $inicial['filtros'][$chave] }}</span></span>
                            <x-prototipo.icone nome="fechar" class="size-4" />
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Opções do chip aberto. O mockup não desenha o menu: chips de opção por baixo da linha
                 (um menu flutuante ficaria cortado pelo overflow da linha de chips). --}}
            <div id="opcoes-filtro" x-show="aberto" x-cloak class="mt-2 flex flex-wrap gap-2 rounded-lg border border-border bg-surface p-3">
                <template x-for="opcao in (aberto ? opcoes[aberto] : [])" :key="opcao">
                    <button type="button" x-on:click="escolher(opcao)" x-text="opcao"
                            class="{{ $chipBase }} border-border-input bg-surface px-3.5 text-text hover:bg-surface-warm"></button>
                </template>
            </div>
        </div>

        @if ($mostraGrelha)
            <div x-show="visiveis > 0" class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3"
                 @if ($estado === 'vazio-filtro') x-cloak @endif>
                @foreach ($lista as $artigo)
                    <div class="grid" x-show="passa(@js($artigo['ref']))">
                        <x-prototipo.cartao-artigo :artigo="$artigo" />
                    </div>
                @endforeach
            </div>

            {{-- Vazio por filtro: aparece sozinho quando a pesquisa/filtros não dão nada --}}
            <div x-show="visiveis === 0" @unless ($estado === 'vazio-filtro') x-cloak @endunless>
                <x-prototipo.estado-vazio icone="lupa" titulo="Nenhum artigo encontrado">
                    <x-slot:corpo><span x-text="textoSemResultados">Nada corresponde a «lanyard» com estes filtros. Experimente outra palavra ou retire filtros.</span></x-slot:corpo>
                    <button type="button" x-on:click="limpar()"
                            class="flex h-12 items-center justify-center rounded-lg border-[1.5px] border-brand bg-surface px-5 text-base font-bold text-brand hover:bg-surface-warm">Limpar filtros</button>
                </x-prototipo.estado-vazio>
            </div>
        @elseif ($estado === 'a-carregar')
            <div aria-busy="true" aria-label="A carregar artigos" class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">
                @for ($i = 0; $i < 8; $i++)
                    <div class="flex flex-col overflow-hidden rounded-lg border border-border bg-surface">
                        <div class="aspect-square bg-skeleton"></div>
                        <div class="flex flex-col gap-2 p-3">
                            <div class="h-3.5 w-[90%] rounded bg-skeleton"></div>
                            <div class="h-3.5 w-[60%] rounded bg-skeleton"></div>
                            <div class="h-3 w-[40%] rounded bg-skeleton-2"></div>
                            <div class="mt-1.5 h-11 rounded-lg bg-skeleton-2"></div>
                        </div>
                    </div>
                @endfor
            </div>
        @elseif ($estado === 'vazio')
            <x-prototipo.estado-vazio icone="caixa" titulo="Ainda não há artigos no catálogo"
                texto="Quando o admin publicar artigos no backoffice, aparecem aqui." />
        @elseif ($estado === 'erro')
            <x-prototipo.estado-vazio icone="sem-wifi" tom="erro" titulo="Não foi possível carregar os artigos"
                texto="Está sem rede e o catálogo ainda não foi descarregado neste dispositivo.">
                <a href="{{ request()->fullUrlWithQuery(['estado' => 'cheio']) }}"
                   class="flex h-12 items-center justify-center rounded-lg bg-brand px-5 text-base font-bold text-bg hover:bg-brand-hover">Tentar outra vez</a>
                <a href="{{ route('prototipo.offline') }}"
                   class="flex h-12 items-center justify-center text-[15px] font-bold text-brand underline">Como usar sem rede</a>
            </x-prototipo.estado-vazio>
        @endif
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            const normalizar = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
            // Faixas de preço de exemplo, sobre o «desde» do artigo (eixos por confirmar, PRD §11).
            const faixas = {
                'até 1 €': (p) => p < 1,
                '1 € a 5 €': (p) => p >= 1 && p <= 5,
                'mais de 5 €': (p) => p > 5,
            };

            window.Alpine.data('listaArtigos', (indice, inicial, opcoes) => ({
                indice,
                opcoes,
                q: inicial.q,
                filtros: { ...inicial.filtros },
                aberto: null,

                passa(ref) {
                    const a = this.indice.find((x) => x.ref === ref);
                    const f = this.filtros;
                    const termo = normalizar(this.q);
                    if (termo && !a.texto.includes(termo)) return false;
                    if (f.categoria && a.categoria !== f.categoria) return false;
                    if (f.tipo && a.tipo !== f.tipo) return false;
                    if (f.cor && !a.cores.includes(f.cor)) return false;
                    // Com os preços escondidos o filtro de preço não conta (o chip desaparece).
                    if (f.preco && this.$store.proto.precosVisiveis && (a.desde === null || !faixas[f.preco](a.desde))) return false;
                    return true;
                },
                get visiveis() {
                    return this.indice.filter((a) => this.passa(a.ref)).length;
                },
                get contagem() {
                    return this.visiveis === 1 ? '1 artigo' : `${this.visiveis} artigos`;
                },
                get textoSemResultados() {
                    const q = this.q.trim();
                    return (q ? `Nada corresponde a «${q}» com estes filtros.` : 'Nada corresponde a estes filtros.')
                        + ' Experimente outra palavra ou retire filtros.';
                },
                abrir(chave) {
                    this.aberto = this.aberto === chave ? null : chave;
                },
                escolher(opcao) {
                    this.filtros[this.aberto] = opcao;
                    this.aberto = null;
                },
                retirar(chave) {
                    this.filtros[chave] = null;
                },
                limpar() {
                    this.q = '';
                    this.filtros = { categoria: null, tipo: null, cor: null, preco: null };
                    this.aberto = null;
                },
            }));
        });
    </script>
</x-prototipo.layout>
