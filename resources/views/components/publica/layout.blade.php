{{--
    Moldura das páginas públicas (partilhas): sem a barra da app do vendedor, sem scripts, nunca indexada.
    Props: titulo (string, <title>). Slot: conteúdo (cada página põe o seu <main>).
--}}
@props(['titulo' => null])
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $titulo ? $titulo.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-bg font-sans text-text antialiased [overflow-wrap:anywhere]">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2">Saltar para o conteúdo</a>

    <header class="border-b border-border bg-surface">
        <div class="mx-auto flex max-w-[1240px] items-center px-4 py-3">
            <x-app.logo />
        </div>
    </header>

    <div id="conteudo">
        {{ $slot }}
    </div>

    <footer class="border-t border-border bg-surface-warm">
        <div class="mx-auto max-w-[1240px] p-4 text-[13px] text-muted-2">Maravilha · brindes e materiais personalizados</div>
    </footer>
</body>
</html>
