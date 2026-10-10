{{--
    Ecrã 1: Entrar (LoginB.dc.html). Estados: vazio · a-carregar · erro. Variáveis: $estado, $estados.
    Protótipo: não há auth. «Entrar» simula «a carregar» ~0,8 s e segue para a lista de artigos.
    Os campos não têm `name`: sem Alpine, o formulário vai para a lista sem pôr credenciais na URL.
--}}
@php
    $carregar = $estado === 'a-carregar';
    $erro = $estado === 'erro';
    $cheio = $estado !== 'vazio';
    $campo = 'h-12 w-full rounded-lg bg-surface px-3.5 text-base text-text placeholder:text-muted disabled:cursor-not-allowed disabled:text-muted';
    $campoOk = 'border-[1.5px] border-border-input';
    $campoErro = 'border-2 border-error';
@endphp

<x-prototipo.layout titulo="Entrar" :estados="$estados" :estado="$estado" sem-app-bar>
    <main class="flex min-h-[calc(100dvh-6rem)] items-center justify-center px-5 py-8">
        <div class="flex w-full max-w-[400px] flex-col gap-7">
            <div class="flex justify-center">
                <x-prototipo.logo grande />
            </div>

            <form
                method="GET"
                action="{{ route('prototipo.artigos') }}"
                x-data="{ carregar: @js($carregar) }"
                @submit.prevent="carregar = true; setTimeout(() => window.location.href = $el.action, 800)"
                class="flex flex-col gap-5 rounded-lg border border-border bg-surface px-6 py-7"
                novalidate
            >
                <div class="flex flex-col gap-1.5">
                    <h1 class="font-display text-[28px] font-semibold">Entrar</h1>
                    <p class="text-[15px] leading-normal text-muted">Use o email e a password da sua conta.</p>
                </div>

                @if ($erro)
                    <x-prototipo.alerta tipo="erro">Email ou password errados. Confirme e tente outra vez.</x-prototipo.alerta>
                @endif

                <div class="flex flex-col gap-2">
                    <label for="login-email" class="text-sm font-semibold">Email</label>
                    <input
                        id="login-email"
                        type="email"
                        autocomplete="username"
                        placeholder="nome@example.com"
                        value="{{ $cheio ? 'rita.sousa@example.com' : '' }}"
                        @disabled($carregar)
                        :disabled="carregar"
                        class="{{ $campo }} {{ $campoOk }}"
                    >
                </div>

                <div class="flex flex-col gap-2">
                    <label for="login-password" class="text-sm font-semibold">Password</label>
                    <input
                        id="login-password"
                        type="password"
                        autocomplete="current-password"
                        value="{{ $cheio ? '••••••••' : '' }}"
                        @disabled($carregar)
                        :disabled="carregar"
                        @if ($erro) aria-invalid="true" aria-describedby="login-password-erro" @endif
                        class="{{ $campo }} {{ $erro ? $campoErro : $campoOk }}"
                    >
                    @if ($erro)
                        <span id="login-password-erro" class="text-sm font-semibold text-error">Verifique a password. Atenção às maiúsculas.</span>
                    @endif
                </div>

                <button
                    type="submit"
                    @disabled($carregar)
                    :disabled="carregar"
                    :aria-busy="carregar"
                    class="flex h-12 items-center justify-center rounded-lg bg-brand px-5 text-base font-bold text-bg hover:bg-brand-hover disabled:cursor-not-allowed disabled:bg-disabled disabled:text-disabled-text"
                >
                    <span x-show="!carregar" @if ($carregar) x-cloak @endif>Entrar</span>
                    <span x-show="carregar" @unless ($carregar) x-cloak @endunless class="inline-flex items-center gap-2.5">
                        <x-prototipo.icone nome="spinner" class="size-[18px]" />
                        A entrar…
                    </span>
                </button>

                <a href="{{ route('prototipo.recuperar') }}" class="inline-flex min-h-11 items-center self-center px-2 text-[15px] font-semibold text-brand underline hover:text-text">Esqueci-me da password</a>
            </form>
        </div>
    </main>
</x-prototipo.layout>
