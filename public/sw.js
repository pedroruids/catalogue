/*
 * Service worker da app do vendedor (Onda 5a, Xavier #10).
 * Âmbito deliberadamente estreito: só intervém em GET de imagens reduzidas do catálogo
 * (/storage/…), servindo-as da cópia local quando existem. Não toca no /admin (Filament), nas
 * páginas públicas /p/{código}, no Livewire nem em nenhum POST. Páginas offline e fallback de
 * navegação são a Onda 5b (#13).
 */
const CACHE_IMAGENS = 'catalogo-imagens-v1'; // o mesmo nome que resources/js/offline/catalogo.js
const PREFIXO_IMAGENS = '/storage/';
const FORA_DO_AMBITO = ['/admin', '/p/', '/livewire'];

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (evento) => {
    evento.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (evento) => {
    const pedido = evento.request;
    if (pedido.method !== 'GET') return;

    const url = new URL(pedido.url);
    if (url.origin !== self.location.origin) return;
    if (FORA_DO_AMBITO.some((p) => url.pathname.startsWith(p))) return;
    if (!url.pathname.startsWith(PREFIXO_IMAGENS)) return;

    evento.respondWith(
        caches.open(CACHE_IMAGENS).then(async (cache) => (await cache.match(pedido)) ?? fetch(pedido)),
    );
});
