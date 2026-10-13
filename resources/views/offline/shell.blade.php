{{--
    Shell offline da app do vendedor (Onda 5b, Xavier #13). O service worker guarda esta página e serve-a
    no lugar de /artigos e /artigos/{id} quando a rede falha; o JavaScript (resources/js/offline/shell.js)
    desenha o ecrã a partir da cópia local. ESTÁTICA POR DESIGN: sem sessão, sem utilizador, sem CSRF e sem
    dados — a mesma para toda a gente, por isso pode viver na cache sem expor nada de ninguém.
    Os ícones vêm dos mesmos <x-app.icone> das páginas online, em <template>, para o cliente os clonar.
--}}
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#1E3A5F">
    <link rel="manifest" href="/manifest.webmanifest">
    {{-- Sem ícone próprio aqui, o browser pedia /favicon.ico à rede morta e enchia a consola de erros. --}}
    <link rel="icon" href="data:,">
    <title>Maravilha</title>
    @vite(['resources/css/app.css', 'resources/js/offline/shell.js'])
</head>
<body data-app class="min-h-dvh bg-bg font-sans text-text antialiased [overflow-wrap:anywhere]">
    <div id="offline-raiz">
        <p class="px-4 py-12 text-center text-muted">A abrir o catálogo…</p>
    </div>
    <noscript><p class="px-4 py-12 text-center">Precisa de JavaScript para ver o catálogo sem rede.</p></noscript>

    @foreach (['chevron-esquerda', 'chevron-baixo', 'fechar', 'saco', 'olho', 'olho-cortado', 'utilizador', 'lupa', 'caixa', 'foto', 'sem-wifi'] as $nome)
        <template id="icone-{{ $nome }}"><x-app.icone :nome="$nome" /></template>
    @endforeach
</body>
</html>
