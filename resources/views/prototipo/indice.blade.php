{{-- Índice do protótipo: ligações para os 7 ecrãs. --}}
@php
    $ecras = [
        ['1', 'Login', route('prototipo.login'), 'Entrar com email e password.'],
        ['2', 'Recuperação de password', route('prototipo.recuperar'), 'Pedir ligação e definir password nova (60 min).'],
        ['3', 'Lista de artigos', route('prototipo.artigos'), 'Pesquisa, filtros, cartões e «Juntar».'],
        ['4', 'Página do artigo', route('prototipo.artigo', 'MRV-1005'), 'Galeria, variantes e preço por quantidade.'],
        ['5', 'Criar partilha', route('prototipo.partilhar'), 'Selecção, o que o cliente vê e validade.'],
        ['6', 'Catálogo no dispositivo', route('prototipo.offline'), 'Descarregar o catálogo para usar sem rede.'],
        ['7', 'Página pública — com preços', route('prototipo.publica', 'com-precos'), 'O que o cliente final recebe.'],
        ['7', 'Página pública — sem preços', route('prototipo.publica', 'sem-precos'), 'O servidor não envia preços.'],
        ['7', 'Página pública — só imagens', route('prototipo.publica', 'so-imagens'), 'Galeria com o nome de cada artigo.'],
    ];
@endphp
<x-prototipo.layout titulo="Protótipo" sem-app-bar :estados="$estados" :estado="$estado">
    <main class="mx-auto flex max-w-[640px] flex-col gap-6 px-4 pb-10 pt-8">
        <x-prototipo.logo />
        <div class="flex flex-col gap-2">
            <h1 class="font-display text-[28px] font-semibold">Protótipo navegável</h1>
            <p class="text-[15px] leading-normal text-muted">
                Ecrãs 1-7 da app {{ config('app.name') }}, sem base de dados e com dados fictícios.
                A barra no fundo troca o estado de cada ecrã e simula a falta de rede.
                O interruptor de preços e a selecção ficam guardados neste browser.
            </p>
        </div>
        <ol class="flex flex-col overflow-hidden rounded-lg border border-border bg-surface">
            @foreach ($ecras as [$n, $nome, $url, $descricao])
                <li class="border-b border-border-soft last:border-b-0">
                    <a href="{{ $url }}" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-warm">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-surface-warm text-sm font-bold text-brand tabular-nums">{{ $n }}</span>
                        <span class="flex flex-col">
                            <span class="font-bold text-brand">{{ $nome }}</span>
                            <span class="text-sm text-muted">{{ $descricao }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </main>
</x-prototipo.layout>
