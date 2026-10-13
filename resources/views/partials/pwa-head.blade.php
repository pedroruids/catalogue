{{-- PWA da app do vendedor (Onda 5a): manifest, cores e meta tags do iOS. O logótipo oficial não existe (BRAND.md): ícones provisórios. --}}
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#1E3A5F">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Maravilha">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon-180.png">
<meta name="catalogo-snapshot-url" content="{{ route('offline.catalog', absolute: false) }}">
<meta name="catalogo-utilizador" content="{{ auth()->id() }}">
