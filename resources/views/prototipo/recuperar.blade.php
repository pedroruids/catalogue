{{--
    Ecrã 2: Recuperar password (RecuperacaoB.dc.html). Variáveis: $estado, $estados.
    Estados: passo-1 · passo-2 · a-carregar · erro-email · erro-coincidem · expirada · enviado.
    Protótipo: nada é enviado. «Enviar ligação» simula «a enviar» ~0,8 s e mostra «enviado»;
    «Guardar password» volta ao login. A ligação vale 60 min (D10). «Enviado» nunca revela se a conta existe.
    Os campos não têm `name`: sem Alpine, nenhum valor vai para a URL.
--}}
@php
    $passo1 = in_array($estado, ['passo-1', 'a-carregar', 'erro-email'], true);
    $passo2 = in_array($estado, ['passo-2', 'erro-coincidem'], true);
    $carregar = $estado === 'a-carregar';
    $campo = 'h-12 w-full rounded-lg bg-surface px-3.5 text-base text-text placeholder:text-muted disabled:cursor-not-allowed disabled:text-muted';
    $campoOk = 'border-[1.5px] border-border-input';
    $campoErro = 'border-2 border-error';
    $botao = 'flex h-12 items-center justify-center rounded-lg px-5 text-base font-bold';
    $primario = $botao.' bg-brand text-bg hover:bg-brand-hover';
    $titulo = 'font-display text-[28px] font-semibold';
    $tituloEstado = 'font-display text-[26px] font-semibold';
    $sobretitulo = 'text-[13px] font-bold uppercase tracking-[0.04em] text-muted';
    $texto = 'text-[15px] leading-normal text-pretty';
    $erroTexto = 'text-sm font-semibold text-error';
@endphp

<x-prototipo.layout titulo="Recuperar password" :estados="$estados" :estado="$estado" sem-app-bar>
    <main class="flex min-h-[calc(100dvh-6rem)] items-center justify-center px-5 py-8">
        <div class="flex w-full max-w-[400px] flex-col gap-7">
            <div class="flex justify-center">
                <x-prototipo.logo grande />
            </div>

            <div class="flex flex-col gap-5 rounded-lg border border-border bg-surface px-6 py-7">
                @if ($passo1)
                    <form
                        method="GET"
                        action="{{ route('prototipo.recuperar') }}"
                        x-data="{ carregar: @js($carregar) }"
                        @submit.prevent="carregar = true; setTimeout(() => window.location.href = @js(route('prototipo.recuperar', ['estado' => 'enviado'])), 800)"
                        class="flex flex-col gap-5"
                        novalidate
                    >
                        <div class="flex flex-col gap-1.5">
                            <span class="{{ $sobretitulo }}">Passo 1 de 2</span>
                            <h1 class="{{ $titulo }}">Recuperar password</h1>
                            <p class="{{ $texto }} text-muted">Indique o email da conta. Enviamos uma ligação para definir uma password nova.</p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="recuperar-email" class="text-sm font-semibold">Email</label>
                            @if ($estado === 'erro-email')
                                <input id="recuperar-email" type="email" autocomplete="email" value="rita.sousa@maravilha"
                                    aria-invalid="true" aria-describedby="recuperar-email-erro" class="{{ $campo }} {{ $campoErro }}">
                                <span id="recuperar-email-erro" role="alert" class="{{ $erroTexto }}">O email parece incompleto. Exemplo: nome@example.com</span>
                            @else
                                <input id="recuperar-email" type="email" autocomplete="email" placeholder="nome@example.com"
                                    value="{{ $carregar ? 'rita.sousa@example.com' : '' }}"
                                    @disabled($carregar) :disabled="carregar" class="{{ $campo }} {{ $campoOk }}">
                            @endif
                        </div>

                        <button
                            type="submit"
                            @disabled($carregar)
                            :disabled="carregar"
                            :aria-busy="carregar"
                            class="{{ $primario }} disabled:cursor-not-allowed disabled:bg-disabled disabled:text-disabled-text"
                        >
                            <span x-show="!carregar" @if ($carregar) x-cloak @endif>Enviar ligação</span>
                            <span x-show="carregar" @unless ($carregar) x-cloak @endunless class="inline-flex items-center gap-2.5">
                                <x-prototipo.icone nome="spinner" class="size-[18px]" />
                                A enviar…
                            </span>
                        </button>
                    </form>
                @elseif ($passo2)
                    <form method="GET" action="{{ route('prototipo.login') }}" class="flex flex-col gap-5" novalidate>
                        <div class="flex flex-col gap-1.5">
                            <span class="{{ $sobretitulo }}">Passo 2 de 2</span>
                            <h1 class="{{ $titulo }}">Nova password</h1>
                            <p id="recuperar-regra" class="{{ $texto }} text-muted">Pelo menos 8 caracteres.</p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="recuperar-nova" class="text-sm font-semibold">Nova password</label>
                            <input id="recuperar-nova" type="password" autocomplete="new-password" aria-describedby="recuperar-regra"
                                value="{{ $estado === 'erro-coincidem' ? 'Maravilha-2026' : '' }}" class="{{ $campo }} {{ $campoOk }}">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="recuperar-confirmar" class="text-sm font-semibold">Confirmar password</label>
                            @if ($estado === 'erro-coincidem')
                                <input id="recuperar-confirmar" type="password" autocomplete="new-password" value="Maravilha-2025"
                                    aria-invalid="true" aria-describedby="recuperar-confirmar-erro" class="{{ $campo }} {{ $campoErro }}">
                                <span id="recuperar-confirmar-erro" role="alert" class="{{ $erroTexto }}">As passwords não coincidem. Escreva a mesma nos dois campos.</span>
                            @else
                                <input id="recuperar-confirmar" type="password" autocomplete="new-password" class="{{ $campo }} {{ $campoOk }}">
                            @endif
                        </div>

                        <button type="submit" class="{{ $primario }}">Guardar password</button>
                    </form>
                @elseif ($estado === 'expirada')
                    <div class="flex size-12 items-center justify-center rounded-full bg-error-bg text-error">
                        <x-prototipo.icone nome="relogio" class="size-6" />
                    </div>
                    <div role="alert" class="flex flex-col gap-1.5">
                        <h1 class="{{ $tituloEstado }}">Esta ligação já expirou</h1>
                        <p class="{{ $texto }} text-muted">As ligações valem 60 minutos. Peça uma nova e use-a logo.</p>
                    </div>
                    <a href="{{ route('prototipo.recuperar', ['estado' => 'passo-1']) }}" class="{{ $primario }}">Pedir nova ligação</a>
                @else
                    {{-- enviado: a mesma mensagem exista ou não a conta --}}
                    <div class="flex size-12 items-center justify-center rounded-full bg-success-bg text-success">
                        <x-prototipo.icone nome="envelope" class="size-6" />
                    </div>
                    <div role="status" class="flex flex-col gap-1.5">
                        <h1 class="{{ $tituloEstado }}">Veja o seu email</h1>
                        <p class="{{ $texto }} text-text">Se o email existir, enviámos uma ligação.</p>
                        <p class="{{ $texto }} text-muted">Procure também no spam. A ligação vale 60 minutos.</p>
                    </div>
                    <a href="{{ route('prototipo.login') }}" class="{{ $botao }} border-[1.5px] border-brand bg-surface text-brand">Voltar a entrar</a>
                @endif

                @if ($passo1 || $passo2)
                    <a href="{{ route('prototipo.login') }}" class="inline-flex min-h-11 items-center self-center px-2 text-[15px] font-semibold text-brand underline hover:text-text">Voltar a entrar</a>
                @endif
            </div>
        </div>
    </main>
</x-prototipo.layout>
