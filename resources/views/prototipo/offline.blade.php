{{--
    Ecrã 6: Catálogo no dispositivo (OfflineB.dc.html · README §6).
    Variáveis: $estado (vazio · a-carregar · interrompida · sem-espaco · cheio · cheio-sem-rede), $estados.
    «cheio» e «cheio-sem-rede» trocam entre si com a rede do store; «cheio-sem-rede» simula a falta de rede (não fica gravado).
--}}
@php
    $factosCheio = fn (string $sync): array => [['Última sincronização', $sync], ['Artigos', '124'], ['Tamanho no dispositivo', '186 MB']];

    // tom: ok · neutro · erro — botao: primario · secundario · desactivado
    $cartoes = [
        'vazio' => ['titulo' => 'Ainda não descarregou o catálogo', 'sub' => 'Use rede Wi-Fi, se puder: as imagens ocupam espaço.', 'tom' => 'neutro',
            'factos' => [['Artigos', '124'], ['Tamanho aproximado', '186 MB'], ['Espaço livre', '2,3 GB']], 'acao' => 'Descarregar catálogo', 'botao' => 'primario'],
        'a-carregar' => ['titulo' => 'A descarregar…', 'sub' => 'Pode continuar a usar a app. Não feche o browser.', 'tom' => 'neutro',
            'progresso' => [62, 'Imagens · 77 de 124 artigos', '116 de 186 MB'], 'factos' => [['Espaço livre', '2,2 GB']], 'acao' => 'Cancelar', 'botao' => 'secundario'],
        'interrompida' => ['titulo' => 'A descarga foi interrompida', 'sub' => 'A rede caiu a meio. O que já veio fica guardado.', 'tom' => 'erro',
            'progresso' => [41, '51 de 124 artigos', '76 de 186 MB'], 'factos' => [['Última cópia completa', '08/10/2026, 18:40']], 'acao' => 'Retomar descarga', 'botao' => 'primario'],
        'sem-espaco' => ['titulo' => 'Sem espaço no dispositivo', 'sub' => 'Faltam cerca de 120 MB. Liberte espaço e tente outra vez.', 'tom' => 'erro',
            'factos' => [['Precisa de', '186 MB'], ['Espaço livre', '64 MB']], 'acao' => 'Tentar outra vez', 'botao' => 'primario'],
        'cheio' => ['titulo' => 'Catálogo descarregado', 'sub' => 'Actualiza-se sozinho quando há rede.', 'tom' => 'ok',
            'factos' => $factosCheio('hoje, 09:12'), 'acao' => 'Actualizar agora', 'botao' => 'secundario'],
        'cheio-sem-rede' => ['titulo' => 'A usar a cópia do dispositivo', 'sub' => 'Quando voltar a rede, a cópia actualiza-se.', 'tom' => 'ok',
            'factos' => $factosCheio('08/10/2026, 18:40'), 'acao' => 'Actualizar precisa de rede', 'botao' => 'desactivado'],
    ];

    $cheio = in_array($estado, ['cheio', 'cheio-sem-rede'], true);
    // Nos estados cheios mostram-se os dois cartões, alternados pela rede; nos outros, só o do estado.
    $mostrar = $cheio ? ['cheio', 'cheio-sem-rede'] : [$estado];
    $tons = [
        'ok' => ['bg-success-bg text-success', 'visto'],
        'neutro' => ['bg-surface-warm text-brand', 'descarregar'],
        'erro' => ['bg-error-bg text-error', 'alerta'],
    ];
@endphp
<x-prototipo.layout titulo="Catálogo no dispositivo" :estados="$estados" :estado="$estado"
    :app-bar="['voltar' => 'Artigos', 'semPrecos' => true, 'sync' => $estado === 'cheio' ? 'Ligado · catálogo sincronizado hoje, 09:12' : 'Ligado']">
    <main x-data @if ($estado === 'cheio-sem-rede') x-init="$store.proto.forcarOffline()" @endif
          class="mx-auto flex max-w-[640px] flex-col gap-5 px-4 pb-12 pt-5">
        <div class="flex flex-col gap-1.5">
            <h1 class="font-display text-[28px] font-semibold">Catálogo no dispositivo</h1>
            <p class="text-[15px] leading-normal text-pretty text-muted">Para mostrar artigos onde não há rede.</p>
        </div>

        @foreach ($mostrar as $chave)
            @php $c = $cartoes[$chave]; [$tomClasse, $tomIcone] = $tons[$c['tom']]; @endphp
            <section aria-labelledby="titulo-{{ $chave }}"
                     @if ($cheio) x-show="{{ $chave === 'cheio' ? '! ' : '' }}$store.proto.offline" @if ($chave !== $estado) x-cloak @endif @endif
                     class="flex flex-col gap-[18px] rounded-lg border border-border bg-surface p-5">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full {{ $tomClasse }}">
                        <x-prototipo.icone :nome="$tomIcone" class="size-[22px]" />
                    </span>
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <h2 id="titulo-{{ $chave }}" class="font-display text-[21px] font-semibold text-pretty">{{ $c['titulo'] }}</h2>
                        <span class="text-sm text-pretty text-muted">{{ $c['sub'] }}</span>
                    </div>
                </div>

                @isset($c['progresso'])
                    @php [$pct, $progArtigos, $progMb] = $c['progresso']; @endphp
                    <div class="flex flex-col gap-2">
                        <div role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progresso da descarga"
                             class="h-2.5 overflow-hidden rounded-full bg-border-soft">
                            <div class="h-full rounded-full bg-brand" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="flex flex-wrap justify-between gap-3 text-sm text-muted-2 tabular-nums">
                            <span>{{ $progArtigos }}</span><span>{{ $progMb }}</span>
                        </div>
                    </div>
                @endisset

                <dl class="border-t border-border-soft">
                    @foreach ($c['factos'] as [$rotulo, $valor])
                        <div class="flex justify-between gap-4 border-b border-border-soft py-[11px] text-[15px]">
                            <dt class="text-muted">{{ $rotulo }}</dt>
                            <dd class="text-right font-bold tabular-nums">{{ $valor }}</dd>
                        </div>
                    @endforeach
                </dl>

                @switch($c['botao'])
                    @case('primario')
                        <button type="button" class="flex h-[52px] items-center justify-center gap-2 rounded-lg bg-brand px-5 text-[17px] font-bold text-bg hover:bg-brand-hover">{{ $c['acao'] }}</button>
                        @break
                    @case('secundario')
                        <button type="button" class="flex h-[52px] items-center justify-center gap-2 rounded-lg border-[1.5px] border-brand bg-surface px-5 text-[17px] font-bold text-brand">{{ $c['acao'] }}</button>
                        @break
                    @default
                        <button type="button" disabled class="flex h-[52px] cursor-not-allowed items-center justify-center rounded-lg bg-disabled px-5 text-[17px] font-bold text-disabled-text">{{ $c['acao'] }}</button>
                @endswitch
            </section>
        @endforeach

        <div class="flex items-start gap-3 rounded-lg bg-surface-warm px-4 py-3.5 text-[15px] leading-normal">
            <x-prototipo.icone nome="info" class="mt-0.5 size-5 text-brand" />
            <span class="text-pretty">Sem rede, pode consultar a lista e os artigos. Partilhar e juntar à selecção precisam de rede.</span>
        </div>
    </main>
</x-prototipo.layout>
