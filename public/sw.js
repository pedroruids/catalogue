/*
 * Service worker da app do vendedor (Onda 5a, Xavier #10; navegação offline na Onda 5b, #13).
 *
 * Estratégia, por tipo de pedido (tudo o resto passa direito à rede, sem tocar):
 *  - GET /storage/… (imagens reduzidas): cache-first na cópia local (cache gerida por catalogo.js).
 *  - GET /build/assets/… (JS/CSS/fontes com hash): cache-first na cache do shell, só do que o shell usa.
 *  - Navegação a /artigos, /artigos/{id}, /partilhar: REDE PRIMEIRO, sempre. Só se a rede falhar é que
 *    se serve o shell offline (/offline), uma página ESTÁTICA, sem sessão nem dados de ninguém, que
 *    desenha o ecrã a partir da cópia local. O HTML autenticado do servidor nunca vai para a cache.
 *  - Fora do âmbito, de propósito: /admin (Filament), /p/{código} (partilha pública), /livewire e
 *    qualquer pedido que não seja GET.
 *
 * Versões: o shell e os seus assets vivem em `maravilha-shell-<VERSAO>`; ao activar apaga-se
 * qualquer versão anterior. A cache de imagens (`catalogo-imagens-v1`) é a cópia do vendedor e só
 * se apaga por catalogo.js (logout / outro utilizador) — nunca aqui.
 */
const VERSAO = 'v2';
const CACHE_IMAGENS = 'catalogo-imagens-v1'; // o mesmo nome que resources/js/offline/catalogo.js
const CACHE_SHELL = `maravilha-shell-${VERSAO}`;
const PREFIXO_CACHE_SHELL = 'maravilha-shell-';
const SHELL = '/offline';
const PREFIXO_IMAGENS = '/storage/';
const PREFIXO_ASSETS = '/build/assets/';
const FORA_DO_AMBITO = ['/admin', '/p/', '/livewire'];
const ROTAS_DO_VENDEDOR = [/^\/artigos\/?$/, /^\/artigos\/\d+\/?$/, /^\/partilhar\/?$/];
const RENOVAR_SHELL_AO_FIM_DE_MS = 6 * 60 * 60 * 1000;

self.addEventListener('install', (evento) => {
    // Sem rede na instalação não é motivo para recusar o service worker: o shell chega na
    // primeira navegação com rede (ver `navegar`).
    evento.waitUntil(
        guardarShell()
            .catch(() => {})
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        (async () => {
            for (const nome of await caches.keys()) {
                if (nome.startsWith(PREFIXO_CACHE_SHELL) && nome !== CACHE_SHELL) await caches.delete(nome);
            }
            await self.clients.claim();
        })(),
    );
});

self.addEventListener('fetch', (evento) => {
    const pedido = evento.request;
    if (pedido.method !== 'GET') return;

    const url = new URL(pedido.url);
    if (url.origin !== self.location.origin) return;
    if (FORA_DO_AMBITO.some((p) => url.pathname.startsWith(p))) return;

    if (url.pathname.startsWith(PREFIXO_IMAGENS)) {
        evento.respondWith(
            caches.open(CACHE_IMAGENS).then(async (cache) => (await cache.match(pedido)) ?? fetch(pedido)),
        );
    } else if (url.pathname.startsWith(PREFIXO_ASSETS)) {
        evento.respondWith(
            caches
                .open(CACHE_SHELL)
                .then(async (cache) => (await cache.match(pedido, { ignoreVary: true })) ?? fetch(pedido)),
        );
    } else if (pedido.mode === 'navigate' && ROTAS_DO_VENDEDOR.some((r) => r.test(url.pathname))) {
        evento.respondWith(navegar(evento));
    }
});

/** Rede primeiro; só uma falha de rede (fetch rejeitado) leva ao shell. Respostas HTTP, boas ou más, passam. */
async function navegar(evento) {
    try {
        const resposta = await fetch(evento.request);
        evento.waitUntil(renovarShellSeVelho().catch(() => {}));
        return resposta;
    } catch {
        const cache = await caches.open(CACHE_SHELL);
        return (await cache.match(SHELL, { ignoreVary: true })) ?? Response.error();
    }
}

async function renovarShellSeVelho() {
    const cache = await caches.open(CACHE_SHELL);
    const actual = await cache.match(SHELL, { ignoreVary: true });
    const data = actual ? Date.parse(actual.headers.get('date') ?? '') : Number.NaN;
    if (!Number.isNaN(data) && Date.now() - data < RENOVAR_SHELL_AO_FIM_DE_MS) return;
    await guardarShell();
}

const mesmaOrigem = (texto, base) => {
    try {
        const url = new URL(texto, base);
        return url.origin === self.location.origin ? url : null;
    } catch {
        return null;
    }
};

/**
 * Guarda o shell e o que ele carrega (JS/CSS do HTML e fontes referidas no CSS) e deita fora o resto
 * da cache — assim um deploy novo troca os assets com hash em vez de os acumular.
 */
async function guardarShell() {
    const cache = await caches.open(CACHE_SHELL);

    const resposta = await fetch(SHELL, { credentials: 'omit', cache: 'no-cache' });
    if (!resposta.ok) throw new Error(`shell ${resposta.status}`);
    const html = await resposta.clone().text();

    const manter = new Set([new URL(SHELL, self.location.origin).href]);
    const assets = [...html.matchAll(/(?:href|src)="([^"]*\/build\/assets\/[^"]+)"/g)]
        .map((m) => mesmaOrigem(m[1].replace(/&amp;/g, '&'), self.location.origin))
        .filter(Boolean);

    for (const url of assets) {
        manter.add(url.href);
        let r = await cache.match(url.href);
        if (!r) {
            r = await fetch(url.href, { credentials: 'omit' });
            if (!r.ok) throw new Error(`asset ${r.status}`);
            await cache.put(url.href, r.clone());
        }

        if (url.pathname.endsWith('.css')) {
            const css = await r.clone().text();
            for (const m of css.matchAll(/url\(\s*["']?([^"')]+)["']?\s*\)/g)) {
                const fonte = mesmaOrigem(m[1], url.href);
                if (!fonte || !fonte.pathname.startsWith(PREFIXO_ASSETS)) continue;
                manter.add(fonte.href);
                if (!(await cache.match(fonte.href))) {
                    const f = await fetch(fonte.href, { credentials: 'omit' });
                    if (f.ok) await cache.put(fonte.href, f);
                }
            }
        }
    }

    // O shell só entra depois de os assets estarem todos guardados: nunca fica um shell sem o seu JS.
    await cache.put(SHELL, resposta);

    for (const pedido of await cache.keys()) {
        if (!manter.has(pedido.url)) await cache.delete(pedido);
    }
}
