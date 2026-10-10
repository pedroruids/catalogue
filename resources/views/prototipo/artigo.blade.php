{{--
    Ecrã 4: Página do artigo (docs/design_handoff_maravilha_app/README.md §4 · ArtigoB.dc.html).
    Variáveis: $estado, $estados, $ref, $artigo (null se a ref não existe → estado de erro).
    Estados: cheio · a-carregar · vazio · erro.
    Cor e tamanho escolhem a variante; os escalões vêm da variante (PrototipoDados).
    ⚠ Protótipo: os escalões vão no HTML e escondem-se com x-show ($store.proto.precosVisiveis).
    Na implementação real é o servidor que não os envia (D5).
--}}
@php
    use App\Support\PrototipoDados;

    $estado = $artigo === null ? 'erro' : $estado;
    $mostra = in_array($estado, ['cheio', 'vazio'], true);

    if ($mostra && $estado === 'vazio') {
        // Mockup «vazio»: sem imagens, sem escalões, poucas características.
        $artigo['imagens'] = 0;
        $artigo['variantes'] = array_map(fn (array $v): array => [...$v, 'escaloes' => []], $artigo['variantes']);
        $artigo['campos'] = array_slice($artigo['campos'], 0, 4, true);
    }

    $variantes = $mostra ? array_map(fn (array $v): array => [
        'cor' => $v['cor'],
        'tamanho' => $v['tamanho'],
        'escaloes' => array_map(fn (array $e): array => [
            'q' => number_format($e['min'], 0, ',', "\u{00A0}").' un.',
            'p' => PrototipoDados::euros($e['preco']),
        ], $v['escaloes']),
    ], $artigo['variantes']) : [];

    $inicial = $mostra ? [
        'ref' => $artigo['ref'],
        'nome' => mb_strtolower($artigo['nome']),
        'cor' => $artigo['cores'][0]['nome'],
        'tamanho' => $artigo['tamanhos'][0] ?? null,
        'imagens' => $artigo['imagens'],
    ] : [];

    $primeira = $variantes[0] ?? null;
    $legenda = $primeira ? $primeira['cor'].($primeira['tamanho'] ? ', '.$primeira['tamanho'] : '') : '';

    $botao = 'flex h-12 items-center justify-center gap-2 rounded-lg px-[18px] text-base font-bold';
@endphp

<x-prototipo.layout :titulo="$mostra ? $artigo['nome'] : 'Artigo'" :estados="$estados" :estado="$estado" :app-bar="['voltar' => 'Artigos']">
    <main class="mx-auto max-w-[1240px] px-4 pt-4 pb-12">

        @if ($mostra)
            <div x-data="paginaArtigo(@js($inicial), @js($variantes))" class="flex flex-wrap items-start gap-x-10 gap-y-6">

                {{-- Galeria --}}
                <div class="flex min-w-0 flex-[1_1_440px] flex-col gap-2.5">
                    @if ($artigo['imagens'] > 0)
                        <div class="bg-riscas relative flex aspect-square items-center justify-center rounded-lg px-4 text-center">
                            <span class="text-sm text-img-label" x-text="etiquetaFoto">foto · {{ $inicial['nome'] }} {{ mb_strtolower($inicial['cor']) }}{{ $inicial['tamanho'] ? ' '.$inicial['tamanho'] : '' }}</span>
                            <span class="absolute right-2.5 bottom-2.5 rounded-full bg-text/75 px-2.5 py-1 text-[13px] font-semibold text-bg tabular-nums"
                                  x-text="`${img + 1} / ${imagens}`">1 / {{ $artigo['imagens'] }}</span>
                        </div>
                        <div class="flex gap-2 overflow-x-auto pb-0.5" role="group" aria-label="Imagens">
                            @for ($i = 0; $i < $artigo['imagens']; $i++)
                                <button type="button" x-on:click="img = {{ $i }}" aria-label="Imagem {{ $i + 1 }} de {{ $artigo['imagens'] }}"
                                        :aria-current="img === {{ $i }} ? 'true' : null" @if ($i === 0) aria-current="true" @endif
                                        :class="img === {{ $i }} ? 'border-2 border-brand font-bold text-brand' : 'border border-border text-img-label'"
                                        class="size-[60px] shrink-0 rounded-lg bg-img-placeholder text-xs tabular-nums">{{ $i + 1 }}</button>
                            @endfor
                        </div>
                    @else
                        <div class="flex aspect-square flex-col items-center justify-center gap-2 rounded-lg bg-skeleton-2 text-muted">
                            <x-prototipo.icone nome="foto" class="size-9" />
                            <span class="text-sm">Sem imagens</span>
                        </div>
                    @endif
                </div>

                {{-- Informação --}}
                <div class="flex min-w-0 flex-[1_1_340px] flex-col gap-6">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-[13px] font-bold tracking-[0.04em] text-muted uppercase">{{ $artigo['categoria'] }}</span>
                        <h1 class="font-display text-[30px] leading-[1.15] font-semibold text-pretty">{{ $artigo['nome'] }}</h1>
                        <span class="text-sm text-muted tabular-nums">Ref. {{ $artigo['ref'] }}</span>
                    </div>

                    {{-- Acções: precisam de rede (D4) --}}
                    <div class="flex flex-col gap-2">
                        <div x-show="! $store.proto.offline" class="flex flex-wrap gap-2.5">
                            <a href="{{ route('prototipo.partilhar') }}" x-on:click="$store.proto.juntar(ref)"
                               class="{{ $botao }} flex-[1_1_140px] bg-brand text-bg hover:bg-brand-hover">
                                <x-prototipo.icone nome="partilhar" class="size-[18px]" />
                                Partilhar
                            </a>
                            <button type="button" x-on:click="$store.proto.toggleSeleccao(ref)"
                                    :aria-pressed="$store.proto.naSeleccao(ref).toString()" aria-pressed="false"
                                    :class="$store.proto.naSeleccao(ref) ? 'bg-surface-warm' : 'bg-surface'"
                                    class="{{ $botao }} flex-[1_1_160px] border-[1.5px] border-brand text-brand">
                                <span x-show="! $store.proto.naSeleccao(ref)" class="flex items-center gap-2"><x-prototipo.icone nome="mais" class="size-[18px]" /> Juntar à selecção</span>
                                <span x-show="$store.proto.naSeleccao(ref)" x-cloak class="flex items-center gap-2"><x-prototipo.icone nome="visto" class="size-[18px]" /> Na selecção</span>
                            </button>
                        </div>
                        <div x-show="$store.proto.offline" x-cloak class="flex flex-col gap-2">
                            <div class="flex flex-wrap gap-2.5">
                                <button type="button" disabled class="{{ $botao }} flex-[1_1_140px] cursor-not-allowed bg-disabled text-disabled-text">Partilhar</button>
                                <button type="button" disabled class="{{ $botao }} flex-[1_1_160px] cursor-not-allowed bg-disabled text-disabled-text">Juntar à selecção</button>
                            </div>
                            <span class="text-sm text-muted-2">Partilhar precisa de rede. Pode continuar a consultar o artigo.</span>
                        </div>
                    </div>

                    {{-- Cor --}}
                    <div class="flex flex-col gap-2.5">
                        <span id="rotulo-cor" class="text-sm font-bold">Cor: <span class="font-medium" x-text="cor">{{ $inicial['cor'] }}</span></span>
                        <div class="flex flex-wrap gap-1.5" role="group" aria-labelledby="rotulo-cor">
                            @foreach ($artigo['cores'] as $c)
                                <button type="button" x-on:click="cor = @js($c['nome'])" aria-label="{{ $c['nome'] }}"
                                        :aria-pressed="(cor === @js($c['nome'])).toString()" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                        class="flex size-11 items-center justify-center rounded-full">
                                    <span :class="cor === @js($c['nome']) ? 'size-10 border-2 border-brand' : 'size-[34px]'"
                                          class="flex items-center justify-center rounded-full">
                                        {{-- hex da amostra: vem dos dados do artigo, não é token --}}
                                        <span :class="cor === @js($c['nome']) ? 'size-[30px]' : 'size-[34px]'"
                                              class="rounded-full ring-1 ring-text/18 ring-inset"
                                              style="background: {{ $c['hex'] }}"></span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tamanho --}}
                    @if ($artigo['tamanhos'])
                        <div class="flex flex-col gap-2.5">
                            <span id="rotulo-tamanho" class="text-sm font-bold">Tamanho</span>
                            <div class="flex flex-wrap gap-2" role="group" aria-labelledby="rotulo-tamanho">
                                @foreach ($artigo['tamanhos'] as $t)
                                    <button type="button" x-on:click="tamanho = @js($t)"
                                            :aria-pressed="(tamanho === @js($t)).toString()" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                            :class="tamanho === @js($t) ? 'border-2 border-brand bg-brand font-bold text-bg' : 'border-[1.5px] border-border-input bg-surface font-semibold text-text'"
                                            class="h-11 min-w-[88px] rounded-lg px-3.5 text-[15px]">{{ $t }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Preço por quantidade (escalões da variante) --}}
                    <section class="flex flex-col gap-2.5" aria-labelledby="titulo-precos">
                        <h2 id="titulo-precos" class="font-display text-xl font-semibold">Preço por quantidade</h2>

                        <div x-show="$store.proto.precosVisiveis" class="flex flex-col gap-2.5">
                            <div x-show="escaloes.length > 0" @if (! $primeira || ! $primeira['escaloes']) x-cloak @endif class="flex flex-col gap-2.5">
                                <span class="text-sm text-muted" x-text="`${legenda} · preço unitário`">{{ $legenda }} · preço unitário</span>
                                <div class="overflow-hidden rounded-lg border border-border bg-surface">
                                    <table class="w-full tabular-nums" data-escaloes>
                                        <thead class="bg-surface-warm text-[13px] font-bold text-muted-2">
                                            <tr>
                                                <th scope="col" class="px-4 py-2.5 text-left font-bold">Quantidade mínima</th>
                                                <th scope="col" class="px-4 py-2.5 text-right font-bold">Preço unitário</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-base">
                                            <template x-for="x in escaloes" :key="x.q">
                                                <tr class="border-t border-border-soft">
                                                    <td class="px-4 py-3" x-text="x.q"></td>
                                                    <td class="px-4 py-3 text-right font-bold text-brand" x-text="x.p"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div x-show="escaloes.length === 0" @if ($primeira && $primeira['escaloes']) x-cloak @endif
                                 class="flex flex-col gap-1 rounded-lg border border-border bg-surface p-4">
                                <span class="text-[17px] font-bold text-brand">Preço sob consulta</span>
                                <span class="text-sm text-muted">Esta variante ainda não tem escalões de preço.</span>
                            </div>
                        </div>

                        <div x-show="! $store.proto.precosVisiveis" x-cloak class="flex items-center gap-3 rounded-lg bg-surface-warm p-4 text-brand">
                            <x-prototipo.icone nome="olho-cortado" class="size-[22px]" />
                            <span class="text-[15px] font-semibold">Preços escondidos</span>
                        </div>
                    </section>

                    {{-- Características --}}
                    <section class="flex flex-col gap-2.5" aria-labelledby="titulo-caracteristicas">
                        <h2 id="titulo-caracteristicas" class="font-display text-xl font-semibold">Características</h2>
                        <dl class="border-t border-border">
                            @foreach ($artigo['campos'] as $rotulo => $valor)
                                <div class="grid grid-cols-[minmax(110px,40%)_1fr] gap-3 border-b border-border py-[11px] text-[15px] leading-[1.45]">
                                    <dt class="text-muted">{{ $rotulo }}</dt>
                                    <dd class="text-pretty text-text">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                </div>
            </div>

        @elseif ($estado === 'a-carregar')
            <div aria-busy="true" aria-label="A carregar o artigo" class="flex flex-wrap gap-x-10 gap-y-6">
                <div class="flex min-w-0 flex-[1_1_440px] flex-col gap-2.5">
                    <div class="aspect-square rounded-lg bg-skeleton"></div>
                    <div class="flex gap-2">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="size-[60px] rounded-lg bg-skeleton-2"></div>
                        @endfor
                    </div>
                </div>
                <div class="flex min-w-0 flex-[1_1_340px] flex-col gap-3">
                    <div class="h-3.5 w-[30%] rounded bg-skeleton"></div>
                    <div class="h-[30px] w-[80%] rounded-md bg-skeleton"></div>
                    <div class="h-3.5 w-[25%] rounded bg-skeleton-2"></div>
                    <div class="mt-2 h-12 rounded-lg bg-skeleton-2"></div>
                    <div class="mt-3 h-[200px] rounded-lg bg-skeleton-2"></div>
                </div>
            </div>

        @else
            <x-prototipo.estado-vazio icone="caixa" titulo="Este artigo já não está no catálogo"
                texto="Pode ter sido retirado ou a ligação estar errada.">
                <a href="{{ route('prototipo.artigos') }}"
                   class="flex h-12 items-center justify-center rounded-lg bg-brand px-5 text-base font-bold text-bg hover:bg-brand-hover">Voltar aos artigos</a>
            </x-prototipo.estado-vazio>
        @endif
    </main>

    @if ($mostra)
        <script>
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('paginaArtigo', (inicial, variantes) => ({
                    ...inicial,
                    variantes,
                    img: 0,

                    get variante() {
                        return this.variantes.find((v) => v.cor === this.cor && v.tamanho === this.tamanho) ?? null;
                    },
                    get escaloes() {
                        return this.variante?.escaloes ?? [];
                    },
                    get legenda() {
                        return this.cor + (this.tamanho ? `, ${this.tamanho}` : '');
                    },
                    // Mudar de cor muda a imagem (no protótipo, a etiqueta do espaço reservado).
                    get etiquetaFoto() {
                        return `foto · ${this.nome} ${this.cor.toLowerCase()}${this.tamanho ? ' ' + this.tamanho : ''}`;
                    },
                }));
            });
        </script>
    @endif
</x-prototipo.layout>
