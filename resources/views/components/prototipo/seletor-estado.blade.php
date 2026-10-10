{{--
    Barra do protótipo (só existe aqui): fixa no fundo, troca ?estado= e simula a rede.
    Props: estados (list<string>) · atual (string|null)
    Mantém os outros parâmetros da query. Esconde-se com o botão «Esconder» (lembrado no store).
--}}
@props(['estados' => [], 'atual' => null])

<div x-data class="fixed inset-x-0 bottom-0 z-40 print:hidden" aria-label="Controlos do protótipo" role="region">
    <div x-show="$store.proto.seletorAberto" class="border-t border-text bg-text/95 text-bg">
        <div class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-2">
            <a href="{{ route('prototipo.indice') }}" class="shrink-0 text-xs font-bold underline underline-offset-2">Protótipo</a>
            <div class="flex min-w-0 flex-1 gap-1.5 overflow-x-auto py-0.5">
                @foreach ($estados as $e)
                    <a href="{{ request()->fullUrlWithQuery(['estado' => $e]) }}"
                       @if ($e === $atual) aria-current="true" @endif
                       @class([
                           'flex h-8 shrink-0 items-center rounded-full px-3 text-xs font-semibold whitespace-nowrap',
                           'bg-bg text-text' => $e === $atual,
                           'border border-white/30' => $e !== $atual,
                       ])>{{ $e }}</a>
                @endforeach
            </div>
            <x-prototipo.toggle-rede />
            <button type="button" x-on:click="$store.proto.toggleSeletor()" class="flex size-8 shrink-0 items-center justify-center rounded-full border border-white/30" aria-label="Esconder controlos do protótipo">
                <x-prototipo.icone nome="fechar" class="size-4" />
            </button>
        </div>
    </div>
    <button type="button" x-show="! $store.proto.seletorAberto" x-cloak x-on:click="$store.proto.toggleSeletor()"
            class="absolute bottom-3 right-3 flex h-9 items-center rounded-full bg-text/90 px-3 text-xs font-bold text-bg" aria-label="Mostrar controlos do protótipo">Estados</button>
</div>
