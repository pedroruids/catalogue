{{--
    Moldura de todos os ecrãs do protótipo.
    Props: titulo (string, <title>) · semAppBar (bool: login, recuperação, página pública)
           · appBar (array de props passadas ao <x-prototipo.app-bar>) · estados/estado (para o seletor-estado)
           · semSeletor (bool)
    Slots: default (conteúdo, dentro de <main> é responsabilidade do ecrã) · cabecalho (substitui a AppBar)
--}}
@props([
    'titulo' => null,
    'semAppBar' => false,
    'appBar' => [],
    'estados' => [],
    'estado' => null,
    'semSeletor' => false,
])
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $titulo ? $titulo.' · ' : '' }}{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/prototipo.js'])
    @livewireStyles
</head>
<body data-proto class="min-h-dvh bg-bg font-sans text-text antialiased">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2">Saltar para o conteúdo</a>

    @isset($cabecalho)
        {{ $cabecalho }}
    @elseif (! $semAppBar)
        <x-prototipo.app-bar {{ $attributes->merge($appBar) }} />
    @endisset

    <div id="conteudo" @class(['pb-24' => ! $semSeletor])>
        {{ $slot }}
    </div>

    @unless ($semSeletor)
        <x-prototipo.seletor-estado :estados="$estados" :atual="$estado" />
    @endunless

    @livewireScripts
    @fluxScripts
</body>
</html>
