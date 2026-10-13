/**
 * Cópia local do catálogo (Onda 5a, Xavier #10): snapshot JSON em IndexedDB + imagens reduzidas na
 * Cache Storage. Aqui só se ESCREVE a cópia; lê-la sem rede é a Onda 5b (#13), que reutiliza
 * `lerEstado()` e as stores abaixo (`lerCopia()`, na Onda 5b). IndexedDB «à mão»: duas stores, sem dependência.
 */
const BD = 'maravilha-catalogo';
const BD_VERSAO = 1;
export const CACHE_IMAGENS = 'catalogo-imagens-v1';
const CHAVE = 'estado';
/** Estado do interruptor de preços offline (por sessão do separador/app; ver `vista.js`). */
export const CHAVE_PRECOS = 'catalogo-precos-offline';
export const CHAVE_LISTA = 'catalogo-lista-offline'; // pesquisa/tipo da lista, para o «voltar» do artigo
const PARALELO = 6;
const INTERVALO_REVERIFICAR_MS = 10 * 60 * 1000;

const urlSnapshot = () => document.querySelector('meta[name="catalogo-snapshot-url"]')?.content ?? null;
const utilizadorActual = () => document.querySelector('meta[name="catalogo-utilizador"]')?.content ?? null;

function abrirBD() {
    return new Promise((resolve, reject) => {
        const pedido = indexedDB.open(BD, BD_VERSAO);
        pedido.onupgradeneeded = () => {
            const bd = pedido.result;
            bd.createObjectStore('meta', { keyPath: 'chave' }); // estado da cópia (versão, datas, tamanho)
            bd.createObjectStore('artigos', { keyPath: 'id' });
            bd.createObjectStore('tipos', { keyPath: 'id' });
        };
        pedido.onsuccess = () => resolve(pedido.result);
        pedido.onerror = () => reject(pedido.error);
    });
}

const terminada = (tx) =>
    new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error ?? new Error('transacção abortada'));
    });

/**
 * Apaga a cópia local (IndexedDB + imagens). Chamada ao terminar sessão e quando a cópia é de outro
 * utilizador. Nunca rejeita: o que não for possível apagar não pode impedir o logout.
 */
export async function apagarCopia() {
    const bd = new Promise((resolve) => {
        try {
            const pedido = indexedDB.deleteDatabase(BD);
            pedido.onsuccess = pedido.onerror = pedido.onblocked = () => resolve();
        } catch {
            resolve();
        }
    });
    const imagens = (async () => {
        try {
            await caches.delete(CACHE_IMAGENS);
        } catch {
            // sem Cache Storage: nada a apagar
        }
    })();
    try {
        sessionStorage.removeItem(CHAVE_PRECOS);
        sessionStorage.removeItem(CHAVE_LISTA);
    } catch {
        // sem sessionStorage: nada a apagar
    }
    await Promise.all([bd, imagens]);
}

async function lerEstadoBruto() {
    const bd = await abrirBD();
    try {
        return await new Promise((resolve, reject) => {
            const pedido = bd.transaction('meta').objectStore('meta').get(CHAVE);
            pedido.onsuccess = () => resolve(pedido.result ?? null);
            pedido.onerror = () => reject(pedido.error);
        });
    } finally {
        bd.close();
    }
}

/**
 * Estado da cópia actual (ou null se nunca foi descarregada). Uma cópia de OUTRO utilizador (telemóvel
 * partilhado, logout que não correu) é apagada e conta como inexistente.
 */
export async function lerEstado() {
    const estado = await lerEstadoBruto();
    const utilizador = utilizadorActual();
    if (estado && utilizador && estado.utilizador !== utilizador) {
        await apagarCopia();
        return null;
    }
    return estado;
}

function lerStore(bd, nome) {
    return new Promise((resolve, reject) => {
        const pedido = bd.transaction(nome).objectStore(nome).getAll();
        pedido.onsuccess = () => resolve(pedido.result);
        pedido.onerror = () => reject(pedido.error);
    });
}

/** A cópia inteira para a vista offline: {estado, artigos, tipos}, ou null se não há cópia. */
export async function lerCopia() {
    const estado = await lerEstado();
    if (!estado) return null;
    const bd = await abrirBD();
    try {
        const [artigos, tipos] = await Promise.all([lerStore(bd, 'artigos'), lerStore(bd, 'tipos')]);
        return { estado, artigos, tipos };
    } finally {
        bd.close();
    }
}

/** Substitui a cópia numa só transacção: ou fica tudo novo ou fica tudo como estava. */
async function guardarCopia(snapshot, estado) {
    const bd = await abrirBD();
    try {
        const tx = bd.transaction(['meta', 'artigos', 'tipos'], 'readwrite');
        tx.objectStore('artigos').clear();
        tx.objectStore('tipos').clear();
        snapshot.articles.forEach((a) => tx.objectStore('artigos').put(a));
        snapshot.product_types.forEach((t) => tx.objectStore('tipos').put(t));
        tx.objectStore('meta').put({ chave: CHAVE, ...estado });
        await terminada(tx);
    } finally {
        bd.close();
    }
}

async function actualizarMeta(parcial) {
    const actual = await lerEstado();
    if (!actual) return;
    const bd = await abrirBD();
    try {
        const tx = bd.transaction('meta', 'readwrite');
        tx.objectStore('meta').put({ ...actual, ...parcial });
        await terminada(tx);
    } finally {
        bd.close();
    }
}

const urlsImagens = (snapshot) => [
    ...new Set(snapshot.articles.flatMap((a) => a.images.flatMap((i) => [i.thumb, i.screen]))),
];

/**
 * Descarrega as imagens para a Cache Storage (concorrência limitada). Devolve bytes e falhas.
 * Falha de REDE aborta (a cópia antiga fica intacta); resposta HTTP de erro conta como falha
 * dessa imagem — uma imagem em falta não pode impedir a cópia para sempre.
 */
async function descarregarImagens(urls, aoProgresso) {
    const cache = await caches.open(CACHE_IMAGENS);
    let feitas = 0;
    let bytes = 0;
    let falhas = 0;
    const fila = [...urls];

    async function trabalhador() {
        while (fila.length > 0) {
            const url = fila.shift();
            const existente = await cache.match(url);
            let resposta = existente;
            if (!resposta) {
                const r = await fetch(url, { credentials: 'same-origin' }); // rejeita se sem rede
                if (r.ok) {
                    await cache.put(url, r.clone());
                    resposta = r;
                } else {
                    falhas++;
                }
            }
            if (resposta) bytes += (await resposta.clone().blob()).size;
            aoProgresso(++feitas, urls.length);
        }
    }

    await Promise.all(Array.from({ length: Math.min(PARALELO, urls.length) }, trabalhador));

    return { bytes, falhas };
}

/** Imagens que já não pertencem ao catálogo saem da cache — só DEPOIS de a nova cópia estar guardada. */
async function limparImagensAntigas(urls) {
    const cache = await caches.open(CACHE_IMAGENS);
    const conjunto = new Set(urls.map((u) => new URL(u, location.origin).href));
    for (const pedido of await cache.keys()) {
        if (!conjunto.has(pedido.url)) await cache.delete(pedido);
    }
}

const semEspaco = (erro) => erro?.name === 'QuotaExceededError';

let emCurso = null;

/**
 * Sincroniza a cópia com o servidor. Com `forcar` descarrega mesmo sem cópia anterior; sem ele,
 * só actua se já houver cópia (é o que o arranque automático usa). Resultado:
 * 'actualizada' | 'igual' | 'sem-copia' | 'sem-rede' | 'sem-espaco' | 'erro'.
 */
export function sincronizar({ aoProgresso = () => {} } = {}) {
    emCurso ??= executar(aoProgresso).finally(() => {
        emCurso = null;
    });
    return emCurso;
}

async function executar(aoProgresso) {
    const url = urlSnapshot();
    if (!url) return { resultado: 'erro', erro: 'URL do catálogo em falta' };

    const anterior = await lerEstado();

    let resposta;
    try {
        resposta = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                ...(anterior ? { 'If-None-Match': `"${anterior.versao}"` } : {}),
            },
        });
    } catch {
        return { resultado: 'sem-rede' };
    }

    if (resposta.status === 304) {
        await actualizarMeta({ verificada_em: Date.now() });
        return { resultado: 'igual' };
    }
    if (!resposta.ok) return { resultado: 'erro', erro: `HTTP ${resposta.status}` };

    const texto = await resposta.text();
    const snapshot = JSON.parse(texto);

    let imagens;
    try {
        imagens = await descarregarImagens(urlsImagens(snapshot), aoProgresso);
    } catch (erro) {
        if (semEspaco(erro)) return { resultado: 'sem-espaco' };
        return erro instanceof TypeError ? { resultado: 'sem-rede' } : { resultado: 'erro', erro: String(erro) };
    }

    const agora = Date.now();
    const urls = urlsImagens(snapshot);
    try {
        await guardarCopia(snapshot, {
            utilizador: utilizadorActual(),
            versao: snapshot.version,
            gerada_em: snapshot.generated_at,
            sincronizada_em: agora,
            verificada_em: agora,
            artigos: snapshot.articles.length,
            imagens: urls.length - imagens.falhas,
            imagens_em_falta: imagens.falhas,
            bytes: new Blob([texto]).size + imagens.bytes,
        });
    } catch (erro) {
        // A transacção abortou: a cópia anterior (e as suas imagens) ficam intactas.
        return semEspaco(erro) ? { resultado: 'sem-espaco' } : { resultado: 'erro', erro: String(erro) };
    }

    try {
        await limparImagensAntigas(urls);
    } catch {
        // best-effort: sobram imagens, que a próxima sincronização limpa
    }

    return { resultado: 'actualizada' };
}

/** Pede armazenamento persistente (o Chrome pode despejar IndexedDB/Cache sem avisar). */
export async function pedirPersistencia() {
    try {
        if (navigator.storage?.persist && !(await navigator.storage.persisted())) await navigator.storage.persist();
    } catch {
        // sem suporte: a cópia continua a funcionar, só pode ser despejada
    }
}

export function formatarTamanho(bytes) {
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} kB`;
    return `${(bytes / (1024 * 1024)).toLocaleString('pt-PT', { maximumFractionDigits: 1 })} MB`;
}

export function formatarData(ms) {
    return new Date(ms).toLocaleString('pt-PT', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

/** Ao abrir a app (e ao voltar a ela passado algum tempo): se já há cópia e a versão mudou, actualiza. */
export function arrancarActualizacaoAutomatica() {
    let ultima = 0;

    const verificar = async () => {
        if (!navigator.onLine || Date.now() - ultima < INTERVALO_REVERIFICAR_MS) return;
        ultima = Date.now();
        try {
            if (!(await lerEstado())) return;
            const { resultado } = await sincronizar();
            if (resultado === 'actualizada') window.dispatchEvent(new CustomEvent('catalogo:actualizado'));
        } catch {
            // best-effort: a cópia anterior fica como estava
        }
    };

    verificar();
    document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && verificar());
}
