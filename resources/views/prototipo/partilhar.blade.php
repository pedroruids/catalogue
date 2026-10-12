{{--
    Ecrã 5: Partilhar com o cliente (CriarPartilhaB.dc.html · README §5).
    Variáveis: $estado (form · a-carregar · erro-sem-rede · erro-falhou · vazio · gerada), $estados, $seleccao (exemplo).
    Os artigos vêm de $store.proto.seleccao, cruzados com o catálogo abaixo (só nome e ref, sem preços).
    Sem selecção guardada, usa a de exemplo ($seleccao) para o ecrã não abrir vazio (não fica gravada).
--}}
@php
    $catalogo = collect(\App\Support\PrototipoDados::artigos())
        ->mapWithKeys(fn (array $a): array => [$a['ref'] => ['ref' => $a['ref'], 'nome' => $a['nome']]]);

    $validades = collect([7, 30, 90])
        ->mapWithKeys(fn (int $d): array => [$d => now()->addDays($d)->format('d/m/Y')]);

    $modos = [
        'com-precos' => ['Com preços', 'Artigos, variantes e preços por quantidade.'],
        'sem-precos' => ['Sem preços', 'Artigos e variantes. Nenhum preço é enviado.'],
        'so-imagens' => ['Só imagens', 'Galeria com o nome de cada artigo.'],
    ];

    $config = [
        'catalogo' => $catalogo,
        'exemplo' => array_column($seleccao, 'ref'),
        'validades' => $validades,
        'modos' => collect($modos)->map(fn (array $m): string => $m[0]),
        'estado' => $estado,
        'url' => 'https://example.com/p/7Kq2xV9mRa4dTe',
    ];
@endphp
<x-prototipo.layout titulo="Partilhar com o cliente" :estados="$estados" :estado="$estado" :app-bar="['voltar' => 'Artigos', 'semPrecos' => true]">
    <main x-data="criarPartilha(@js($config))" class="mx-auto flex max-w-[1040px] flex-col gap-5 px-4 pb-12 pt-5">
        <h1 class="font-display text-[28px] font-semibold">Partilhar com o cliente</h1>

        {{-- Vazio --}}
        <template x-if="vazio">
            <x-prototipo.estado-vazio icone="saco" titulo="Ainda não escolheu artigos"
                texto="Na lista ou na página de um artigo, toque em «Juntar» para o trazer para aqui."
                class="rounded-lg border border-border bg-surface">
                <a href="{{ route('prototipo.artigos') }}"
                   class="flex h-12 items-center justify-center rounded-lg bg-brand px-5 text-base font-bold text-bg hover:bg-brand-hover">Ver artigos</a>
            </x-prototipo.estado-vazio>
        </template>

        {{-- Ligação gerada --}}
        <template x-if="! vazio && fase === 'gerada'">
            <section class="flex flex-col gap-[18px] rounded-lg border border-border bg-surface px-5 py-6" aria-labelledby="titulo-pronta">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-success-bg text-success">
                        <x-prototipo.icone nome="visto" class="size-[22px]" />
                    </span>
                    <h2 id="titulo-pronta" class="font-display text-[22px] font-semibold">Ligação pronta</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="flex h-8 items-center rounded-full bg-brand px-3 text-sm font-bold text-bg" x-text="nomeModo"></span>
                    <span class="flex h-8 items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums" x-text="'Válida até ' + expira"></span>
                    <span class="flex h-8 items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums" x-text="contagem"></span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <input x-ref="url" type="text" readonly :value="url" aria-label="Ligação da partilha"
                           x-on:focus="$event.target.select()"
                           class="h-12 min-w-0 flex-[1_1_220px] truncate rounded-lg border-[1.5px] border-border-input bg-bg px-3.5 text-[15px]">
                    <button type="button" x-on:click="copiar()"
                            :class="copiada ? 'bg-success text-surface' : 'bg-brand text-bg hover:bg-brand-hover'"
                            class="flex h-12 flex-none items-center justify-center gap-2 rounded-lg px-5 text-base font-bold">
                        <span x-show="! copiada" class="flex items-center gap-2"><x-prototipo.icone nome="copiar" class="size-[18px]" />Copiar</span>
                        <span x-show="copiada" class="flex items-center gap-2"><x-prototipo.icone nome="visto" class="size-[18px]" />Copiada</span>
                    </button>
                    <span class="sr-only" aria-live="polite" x-text="copiada ? 'Ligação copiada' : ''"></span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-bold text-muted-2">Enviar por</span>
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(110px,1fr))] gap-2">
                        @foreach (['whatsapp' => 'WhatsApp', 'email' => 'Email', 'mais' => 'Mais…'] as $canal => $rotulo)
                            <button type="button" x-on:click="enviar('{{ $canal }}')"
                                    class="h-12 rounded-lg border-[1.5px] border-brand bg-surface text-[15px] font-bold text-brand">{{ $rotulo }}</button>
                        @endforeach
                    </div>
                </div>
                <button type="button" x-on:click="novaPartilha()"
                        class="h-11 self-start px-1 text-[15px] font-bold text-brand underline">Fazer outra partilha</button>
            </section>
        </template>

        {{-- Formulário (form · a-carregar · erro-sem-rede · erro-falhou) --}}
        <div x-show="! vazio && fase !== 'gerada'" @if (in_array($estado, ['vazio', 'gerada'], true)) x-cloak @endif
             class="flex flex-wrap items-start gap-x-10 gap-y-6">
            <section class="flex min-w-0 flex-[1_1_360px] flex-col gap-2.5" aria-labelledby="titulo-artigos">
                <h2 id="titulo-artigos" class="text-base font-bold" x-text="contagem">{{ count($seleccao) }} artigos</h2>
                <ul class="flex flex-col rounded-lg border border-border bg-surface">
                    <template x-for="item in itens" :key="item.ref">
                        <li class="flex items-center gap-3 border-b border-border-soft py-2.5 pl-3 pr-1.5 last:border-b-0">
                            <div class="bg-riscas size-14 shrink-0 rounded-md" aria-hidden="true"></div>
                            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <span class="truncate text-[15px] font-bold leading-snug" x-text="item.nome"></span>
                                <span class="text-[13px] text-muted" x-text="item.ref"></span>
                            </div>
                            <button type="button" x-on:click="retirar(item.ref)" :disabled="bloqueado"
                                    :aria-label="'Retirar ' + item.nome"
                                    class="flex size-11 shrink-0 items-center justify-center rounded-lg text-muted-2 hover:bg-surface-warm disabled:cursor-not-allowed disabled:opacity-50">
                                <x-prototipo.icone nome="fechar" class="size-5" />
                            </button>
                        </li>
                    </template>
                </ul>
            </section>

            <section class="flex min-w-0 flex-[1_1_360px] flex-col gap-5">
                <fieldset class="flex flex-col gap-2.5">
                    <legend class="mb-2.5 text-base font-bold">O que o cliente vê</legend>
                    @foreach ($modos as $valor => [$nome, $desc])
                        <label class="group flex cursor-pointer items-start gap-3 rounded-lg border border-border-input bg-surface px-[17px] py-[15px] hover:border-brand has-checked:border-2 has-checked:border-brand has-checked:px-4 has-checked:py-3.5 has-focus-visible:foco-marca">
                            <input type="radio" name="modo" value="{{ $valor }}" x-model="modo" class="sr-only" @checked($valor === 'sem-precos')>
                            <span class="mt-px flex size-[22px] shrink-0 items-center justify-center rounded-full border-2 border-border-input group-has-checked:border-brand group-has-checked:bg-brand" aria-hidden="true">
                                <span class="hidden size-2 rounded-full bg-bg group-has-checked:block"></span>
                            </span>
                            <span class="flex flex-col gap-0.5">
                                <span class="text-base font-bold">{{ $nome }}</span>
                                <span class="text-sm leading-[1.4] text-muted-2">{{ $desc }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <div class="flex flex-col gap-2.5">
                    <h2 id="titulo-validade" class="text-base font-bold">Validade</h2>
                    <div role="group" aria-labelledby="titulo-validade" class="grid grid-cols-3 overflow-hidden rounded-lg border border-border-input bg-surface">
                        @foreach ($validades->keys() as $dias)
                            <button type="button" x-on:click="validade = {{ $dias }}"
                                    :aria-pressed="(validade === {{ $dias }}).toString()" aria-pressed="{{ $dias === 7 ? 'true' : 'false' }}"
                                    :class="validade === {{ $dias }} ? 'bg-brand text-bg font-bold' : 'bg-surface text-text font-semibold'"
                                    class="h-11 text-[15px]">{{ $dias }} dias</button>
                        @endforeach
                    </div>
                    {{-- TODO: quem revoga (PRD §4) — o vendedor não tem backoffice; texto do mockup mantido. --}}
                    <span class="text-sm text-muted">Expira a <span class="tabular-nums" x-text="expira">{{ $validades[7] }}</span>. Pode revogá-la antes no backoffice.</span>
                </div>

                <div x-show="semRede" @if ($estado !== 'erro-sem-rede') x-cloak @endif>
                    <x-prototipo.alerta tipo="aviso" titulo="Partilhar precisa de rede.">A selecção fica guardada; gere a ligação quando tiver rede.</x-prototipo.alerta>
                </div>
                <div x-show="fase === 'erro-falhou' && ! semRede" @if ($estado !== 'erro-falhou') x-cloak @endif>
                    <x-prototipo.alerta tipo="erro">Não foi possível gerar a ligação. Tente outra vez; a selecção mantém-se.</x-prototipo.alerta>
                </div>

                <button type="button" x-on:click="gerar()" :disabled="bloqueado" @disabled(in_array($estado, ['a-carregar', 'erro-sem-rede'], true))
                        :class="bloqueado ? 'bg-disabled text-disabled-text cursor-not-allowed' : 'bg-brand text-bg hover:bg-brand-hover'"
                        class="flex h-[52px] items-center justify-center gap-2.5 rounded-lg px-5 text-[17px] font-bold">
                    <span x-show="fase === 'a-carregar'" @if ($estado !== 'a-carregar') x-cloak @endif><x-prototipo.icone nome="spinner" class="size-[18px]" /></span>
                    <span x-text="textoGerar">{{ match ($estado) { 'a-carregar' => 'A gerar ligação…', 'erro-falhou' => 'Tentar outra vez', default => 'Gerar ligação' } }}</span>
                </button>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('criarPartilha', (cfg) => ({
                catalogo: cfg.catalogo,
                validades: cfg.validades,
                modos: cfg.modos,
                url: cfg.url,
                modo: 'sem-precos', // D10
                validade: 7, // D10
                fase: ['vazio', 'erro-sem-rede'].includes(cfg.estado) ? 'form' : cfg.estado,
                forcarVazio: cfg.estado === 'vazio',
                copiada: cfg.estado === 'gerada',

                init() {
                    const proto = this.$store.proto;
                    // «erro-sem-rede» simula a falta de rede (não fica gravado).
                    if (cfg.estado === 'erro-sem-rede') proto.forcarOffline();
                    // Sem selecção guardada: usa a de exemplo (não fica gravada).
                    if (!this.forcarVazio && proto.seleccao.filter((r) => this.catalogo[r]).length === 0) {
                        proto.seleccao = [...cfg.exemplo];
                    }
                },
                get itens() {
                    if (this.forcarVazio) return [];
                    return this.$store.proto.seleccao.map((r) => this.catalogo[r]).filter(Boolean);
                },
                get vazio() {
                    return this.itens.length === 0;
                },
                get contagem() {
                    const n = this.itens.length;
                    return n === 1 ? '1 artigo' : n + ' artigos';
                },
                get semRede() {
                    return this.$store.proto.offline;
                },
                get bloqueado() {
                    return this.semRede || this.fase === 'a-carregar';
                },
                get expira() {
                    return this.validades[this.validade];
                },
                get nomeModo() {
                    return this.modos[this.modo];
                },
                get textoGerar() {
                    if (this.fase === 'a-carregar') return 'A gerar ligação…';
                    return this.fase === 'erro-falhou' && !this.semRede ? 'Tentar outra vez' : 'Gerar ligação';
                },

                retirar(ref) {
                    if (this.bloqueado) return;
                    this.$store.proto.retirar(ref);
                },
                gerar() {
                    if (this.bloqueado) return;
                    this.fase = 'a-carregar';
                    setTimeout(() => {
                        this.fase = 'gerada';
                        this.copiada = false;
                    }, 900);
                },
                async copiar() {
                    try {
                        await navigator.clipboard.writeText(this.url);
                        this.copiada = true;
                    } catch {
                        // Sem Clipboard API ou sem permissão: selecciona o campo e tenta o método antigo.
                        try {
                            this.$refs.url.select();
                            this.copiada = document.execCommand('copy');
                        } catch {
                            this.copiada = false;
                        }
                    }
                },
                async enviar(canal) {
                    if (navigator.share) {
                        try {
                            await navigator.share({ title: 'Artigos da Maravilha', url: this.url });
                        } catch {
                            // cancelado
                        }
                        return;
                    }
                    const texto = encodeURIComponent(this.url);
                    if (canal === 'whatsapp') window.open('https://wa.me/?text=' + texto, '_blank', 'noopener');
                    else if (canal === 'email') window.location.href = 'mailto:?subject=' + encodeURIComponent('Artigos da Maravilha') + '&body=' + texto;
                    else this.copiar();
                },
                novaPartilha() {
                    this.fase = 'form';
                    this.copiada = false;
                },
            }));
        });
    </script>
</x-prototipo.layout>
