{{--
    Ecrã 7: Página pública da partilha (PartilhaPublicaB.dc.html · README §7). Sem a navegação da app.
    Variáveis: $estado (cheio · a-carregar · vazio · expirada · revogada · inexistente), $estados, $modo,
    $precosPermitidos, $artigos (já sem preços fora de com-precos), $vendedora.
    Regra D5: os preços saem do servidor só em com-precos. Nenhum texto fixo desta view leva «€»;
    o único «€» vem de PrototipoDados::euros(), dentro de @if ($precosPermitidos).
--}}
@php
    $avisos = [
        'vazio' => ['Estes artigos já não estão disponíveis', 'Saíram do catálogo depois de serem partilhados. Fale com a Maravilha para ver alternativas.'],
        'expirada' => ['Esta ligação expirou', 'Peça a quem lha enviou uma ligação nova.'],
        'revogada' => ['Esta ligação já não está activa', 'Quem a partilhou desactivou-a. Se precisar, peça uma ligação nova.'],
        'inexistente' => ['Não encontrámos esta ligação', 'Confirme se a copiou completa, até ao fim.'],
    ];
    $validaAte = now()->addDays(7)->format('d/m/Y'); // D10: omissão 7 dias
    $etiqueta = fn (array $a): string => mb_strtolower($a['nome']);

    $lista = function (array $itens): string {
        $ultimo = array_pop($itens);

        return $itens === [] ? (string) $ultimo : implode(', ', $itens).' e '.$ultimo;
    };
    $textoVariantes = function (array $a) use ($lista): string {
        $partes = [count($a['cores']) === 1 ? $a['cores'][0]['nome'] : count($a['cores']).' cores'];
        if ($a['tamanhos'] !== []) {
            $partes[] = $lista($a['tamanhos']);
        }

        return implode(' · ', $partes);
    };
@endphp
<x-prototipo.layout titulo="Veja os artigos" :estados="$estados" :estado="$estado" sem-app-bar>
    <x-slot:cabecalho>
        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex max-w-[1240px] items-center px-4 py-3">
                <x-prototipo.logo />
            </div>
        </header>
    </x-slot:cabecalho>

    <main class="mx-auto flex w-full max-w-[1240px] flex-col gap-6 px-4 pb-12 pt-6">
        @if ($estado === 'a-carregar')
            <div aria-busy="true" aria-label="A carregar" class="flex flex-col gap-6">
                <div class="flex flex-col gap-2.5">
                    <div class="h-[30px] w-[55%] rounded-md bg-skeleton"></div>
                    <div class="h-3.5 w-[70%] rounded bg-skeleton-2"></div>
                </div>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-4">
                    @foreach (range(1, 3) as $i)
                        <div class="overflow-hidden rounded-lg border border-border bg-surface">
                            <div class="aspect-[4/3] bg-skeleton"></div>
                            <div class="flex flex-col gap-2 p-4">
                                <div class="h-[18px] w-3/4 rounded bg-skeleton"></div>
                                <div class="h-3 w-[35%] rounded bg-skeleton-2"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif (isset($avisos[$estado]))
            @php [$titulo, $texto] = $avisos[$estado]; @endphp
            <div class="flex flex-col items-center gap-2.5 px-4 py-16 text-center">
                <div class="flex size-16 items-center justify-center rounded-full bg-surface-warm text-brand">
                    <x-prototipo.icone nome="corrente" class="size-7" />
                </div>
                <h1 class="font-display text-2xl font-semibold text-pretty">{{ $titulo }}</h1>
                <p class="max-w-[360px] text-[15px] leading-normal text-pretty text-muted">{{ $texto }}</p>
            </div>
        @else
            <div class="flex flex-col gap-2">
                <h1 class="font-display text-[30px] font-semibold leading-[1.15]">Veja os artigos</h1>
                <p class="text-[15px] leading-normal text-muted-2">Selecção partilhada por <strong class="text-text">{{ $vendedora }}</strong>, da Maravilha.</p>
                <div class="mt-1 flex flex-wrap gap-2">
                    <span class="flex h-[30px] items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums">{{ count($artigos) === 1 ? '1 artigo' : count($artigos).' artigos' }}</span>
                    <span class="flex h-[30px] items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums">Disponível até {{ $validaAte }}</span>
                </div>
            </div>

            @if ($modo === 'so-imagens')
                {{-- Só imagens: 2 por artigo, com o nome (D11) --}}
                <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-3">
                    @foreach ($artigos as $artigo)
                        @foreach (range(1, max(1, min(2, $artigo['imagens']))) as $n)
                            <figure class="flex flex-col gap-1.5">
                                <x-prototipo.img-placeholder class="rounded-lg"
                                    :etiqueta="$artigo['imagens'] ? $n.' · '.$etiqueta($artigo) : null" :sem-imagens="! $artigo['imagens']" />
                                <figcaption class="text-sm font-semibold leading-[1.3] text-pretty">{{ $artigo['nome'] }}</figcaption>
                            </figure>
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-4">
                    @foreach ($artigos as $artigo)
                        <article class="flex flex-col overflow-hidden rounded-lg border border-border bg-surface">
                            <x-prototipo.img-placeholder ratio="4/3"
                                :etiqueta="$artigo['imagens'] ? $etiqueta($artigo) : null" :sem-imagens="! $artigo['imagens']" />
                            <div class="flex flex-col gap-3 p-4">
                                <div class="flex flex-col gap-0.5">
                                    <h2 class="font-display text-xl font-semibold leading-[1.2] text-pretty">{{ $artigo['nome'] }}</h2>
                                    <span class="text-[13px] text-muted">Ref. {{ $artigo['ref'] }}</span>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach ($artigo['cores'] as $cor)
                                            {{-- hex da amostra vem dos dados do artigo (não é token) --}}
                                            <span title="{{ $cor['nome'] }}" class="size-[22px] rounded-full shadow-[inset_0_0_0_1px_rgb(20_32_46/0.18)]" style="background: {{ $cor['hex'] }}"></span>
                                        @endforeach
                                        <span class="sr-only">Cores: {{ collect($artigo['cores'])->pluck('nome')->join(', ') }}</span>
                                    </div>
                                    <span class="text-sm leading-[1.45] text-muted-2">{{ $textoVariantes($artigo) }}</span>
                                </div>

                                @if ($precosPermitidos)
                                    @php
                                        $variante = collect($artigo['variantes'])->first(fn (array $v): bool => ($v['escaloes'] ?? []) !== []);
                                    @endphp
                                    @if ($variante)
                                        <div class="flex flex-col gap-1.5">
                                            <span class="text-[13px] font-bold text-muted-2">Preço unitário · {{ $variante['cor'] }}{{ $variante['tamanho'] ? ', '.$variante['tamanho'] : '' }}</span>
                                            <div class="overflow-hidden rounded-lg border border-border-soft">
                                                @foreach ($variante['escaloes'] as $escalao)
                                                    <div class="flex justify-between border-b border-border-soft px-3 py-2 text-[15px] tabular-nums last:border-b-0">
                                                        <span>desde {{ $escalao['min'] }} un.</span>
                                                        <strong class="text-brand">{{ \App\Support\PrototipoDados::euros($escalao['preco']) }}</strong>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm font-semibold">Preço sob consulta</span>
                                    @endif
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </main>

    <footer class="border-t border-border bg-surface-warm">
        <div class="mx-auto max-w-[1240px] p-4 text-[13px] text-muted-2">Maravilha · brindes e materiais personalizados</div>
    </footer>
</x-prototipo.layout>
