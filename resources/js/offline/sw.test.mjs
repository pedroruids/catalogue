// Fallback do service worker (public/sw.js) exercido a sério, com `self`/`caches`/`fetch` simulados:
// `node --test resources/js/offline/sw.test.mjs`
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const CODIGO = readFileSync(new URL('../../../public/sw.js', import.meta.url), 'utf8');
const ORIGEM = 'https://catalogo.test';

/** Carrega o sw.js num contexto isolado; `fetchSim` é o que a "rede" responde (ou rejeita). */
function carregar(fetchSim) {
    const ouvintes = {};
    const shell = new Response('SHELL OFFLINE', { status: 200 });
    const cache = {
        match: async (chave) => ((typeof chave === 'string' ? chave : chave.url).endsWith('/offline') ? shell : undefined),
        put: async () => {},
        keys: async () => [],
        delete: async () => true,
    };
    const self = {
        location: { origin: ORIGEM },
        addEventListener: (nome, f) => (ouvintes[nome] = f),
        skipWaiting: async () => {},
        clients: { claim: async () => {} },
    };
    vm.runInNewContext(CODIGO, {
        self, Response, URL, Set, Date, Promise, Boolean, Number, Error, console,
        caches: { open: async () => cache, keys: async () => [], delete: async () => true },
        fetch: fetchSim,
    });
    return { shell, ouvintes };
}

/** Dispara um pedido; devolve a resposta dada ao browser, ou `null` se o SW não o tratou. */
async function pedir(ouvintes, caminho, { method = 'GET', mode = 'navigate' } = {}) {
    let promessa = null;
    ouvintes.fetch({
        request: { method, mode, url: ORIGEM + caminho },
        respondWith: (p) => (promessa = p),
        waitUntil: () => {},
    });
    return promessa ? await promessa : null;
}

test('navegação a /artigos com a rede em baixo serve o shell /offline', async () => {
    const { ouvintes, shell } = carregar(async () => {
        throw new TypeError('Failed to fetch');
    });
    for (const caminho of ['/artigos', '/artigos/12', '/partilhar']) {
        assert.equal(await pedir(ouvintes, caminho), shell, caminho);
    }
});

test('resposta HTTP do servidor (200, 500, 302 para /login) passa tal e qual, nunca o shell', async () => {
    for (const [status, headers] of [[200, {}], [500, {}], [302, { location: '/login' }]]) {
        const original = new Response('x', { status, headers });
        const { ouvintes } = carregar(async () => original);
        assert.equal(await pedir(ouvintes, '/artigos'), original, `HTTP ${status}`);
    }
});

test('fora do âmbito: /admin, /p/…, /livewire e qualquer não-GET não são tratados', async () => {
    const { ouvintes } = carregar(async () => {
        throw new TypeError('Failed to fetch');
    });
    for (const caminho of ['/admin', '/admin/articles', '/p/abc123', '/livewire/update', '/login', '/artigos/abc']) {
        assert.equal(await pedir(ouvintes, caminho), null, caminho);
    }
    assert.equal(await pedir(ouvintes, '/artigos', { method: 'POST' }), null, 'POST /artigos');
    assert.equal(await pedir(ouvintes, '/artigos', { mode: 'cors' }), null, 'pedido que não é navegação');
});
