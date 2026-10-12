{{--
    Moldura da app do vendedor (substitui o layout com barra lateral do starter kit nestas páginas).
    Usada como layout de página Livewire: #[Layout('components.app.layout')]. Recebe $slot e $title.
    A app-bar fica dentro da página (precisa das acções Livewire, ex. o interruptor de preços).
--}}
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ filled($title ?? null) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-app class="min-h-dvh bg-bg font-sans text-text antialiased">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2">Saltar para o conteúdo</a>

    <div id="conteudo">
        {{ $slot }}
    </div>
</body>
</html>
