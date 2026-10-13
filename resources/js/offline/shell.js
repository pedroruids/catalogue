/**
 * Shell offline (Onda 5b, Xavier #13): o service worker serve esta página no lugar de /artigos e
 * /artigos/{id} quando a rede falha. Aqui lê-se a cópia local (IndexedDB) e desenha-se o ecrã certo
 * com o mesmo markup das páginas online (render.js). Sem rede nada daqui fala com o servidor.
 */
import { CHAVE_PRECOS, formatarData, lerCopia } from './catalogo';
import {
    appBar,
    artigo,
    artigoNaoEncontrado,
    indiceDaVariante,
    lista,
    POR_PAGINA,
    precisaDeRede,
    semCopia,
} from './render';

const CHAVE_LISTA = 'catalogo-lista-offline'; // pesquisa/tipo da lista, para o «voltar» do artigo
const raiz = document.getElementById('offline-raiz');

const icone = (nome, classe = 'size-5') => {
    const modelo = document.getElementById(`icone-${nome}`);
    if (!modelo) return '';
    const svg = modelo.content.firstElementChild.cloneNode(true);
    svg.setAttribute('class', `shrink-0 ${classe}`.trim());
    return svg.outerHTML;
};

// D17: offline o interruptor começa sempre em «sem preços» e só vale enquanto a sessão durar.
const precosVisiveis = () => {
    try {
        return sessionStorage.getItem(CHAVE_PRECOS) === '1';
    } catch {
        return false;
    }
};
const guardarPrecos = (visiveis) => {
    try {
        sessionStorage.setItem(CHAVE_PRECOS, visiveis ? '1' : '0');
    } catch {
        // sem sessionStorage: o interruptor funciona na mesma, só não sobrevive à navegação
    }
};

let copia = null;
let dataCopia = '';
let desenhar = () => {};
let aoActuar = () => {};
let temporizadorAviso = null;

const estadoLista = () => {
    const p = new URLSearchParams(location.search);
    return { pesquisa: p.get('q') ?? '', tipo: p.get('tipo') ?? '', porPagina: POR_PAGINA };
};

function guardarVistaLista(vista) {
    const p = new URLSearchParams();
    if (vista.pesquisa.trim() !== '') p.set('q', vista.pesquisa);
    if (/^\d+$/.test(vista.tipo)) p.set('tipo', vista.tipo);
    const texto = p.toString();
    history.replaceState(null, '', location.pathname + (texto ? `?${texto}` : ''));
    try {
        sessionStorage.setItem(CHAVE_LISTA, texto);
    } catch {
        // «voltar» fica na lista sem filtros
    }
}

const hrefLista = () => {
    try {
        const texto = sessionStorage.getItem(CHAVE_LISTA);
        return texto ? `/artigos?${texto}` : '/artigos';
    } catch {
        return '/artigos';
    }
};

function avisar(texto) {
    const aviso = document.getElementById('aviso-rede');
    if (!aviso) return;
    aviso.textContent = texto;
    aviso.hidden = false;
    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(() => (aviso.hidden = true), 4000);
}

function montarLista() {
    const vista = estadoLista();
    let temporizador = null;

    desenhar = () => {
        const cabecalho = appBar({ icone, precos: precosVisiveis(), dataCopia });
        raiz.innerHTML = `${cabecalho}${lista({ icone, copia, vista: { ...vista, precos: precosVisiveis() } }).html}`;
        document.title = 'Artigos · Maravilha';
    };

    const redesenharResto = () => {
        const r = lista({ icone, copia, vista: { ...vista, precos: precosVisiveis() } });
        document.getElementById('lista-resto').innerHTML = r.resto;
        document.getElementById('contagem').textContent = r.total === 1 ? '1 artigo' : `${r.total} artigos`;
        guardarVistaLista(vista);
    };

    raiz.addEventListener('input', (evento) => {
        if (evento.target.id !== 'pesquisa') return;
        clearTimeout(temporizador);
        temporizador = setTimeout(() => {
            vista.pesquisa = evento.target.value;
            vista.porPagina = POR_PAGINA;
            redesenharResto();
        }, 150);
    });

    aoActuar = (acao, alvo) => {
        if (acao === 'abrir-tipos') {
            const opcoes = document.getElementById('opcoes-tipo');
            opcoes.hidden = !opcoes.hidden;
            alvo.setAttribute('aria-expanded', String(!opcoes.hidden));
        } else if (acao === 'tipo') {
            vista.tipo = alvo.dataset.tipo;
            vista.porPagina = POR_PAGINA;
            redesenharResto();
        } else if (acao === 'limpar') {
            vista.pesquisa = '';
            vista.tipo = '';
            vista.porPagina = POR_PAGINA;
            desenhar();
            guardarVistaLista(vista);
        } else if (acao === 'mais') {
            vista.porPagina += POR_PAGINA;
            redesenharResto();
        } else if (acao === 'precos') {
            guardarPrecos(!precosVisiveis());
            desenhar();
            guardarVistaLista(vista);
        }
    };

    desenhar();
    guardarVistaLista(vista);
}

function montarArtigo(id) {
    const a = copia.artigos.find((x) => x.id === id);
    if (!a) {
        raiz.innerHTML = appBar({ icone, voltar: { rotulo: 'Artigos', href: hrefLista() }, precos: precosVisiveis(), dataCopia }) + artigoNaoEncontrado({ icone });
        document.title = 'Artigo · Maravilha';
        return;
    }

    const tipo = copia.tipos.find((t) => t.id === a.product_type_id);
    const primeira = a.variants[0] ?? null;
    const posicao = (imagemId) => {
        const n = a.images.findIndex((i) => i.id === imagemId);
        return n === -1 ? null : n;
    };
    const estado = { escolha: { ...(primeira?.options ?? {}) }, imagem: (primeira && posicao(primeira.image_id)) ?? 0 };

    desenhar = () => {
        raiz.innerHTML =
            appBar({ icone, voltar: { rotulo: 'Artigos', href: hrefLista() }, precos: precosVisiveis(), dataCopia }) +
            artigo({ icone, artigo: a, tipo, estado: { ...estado, precos: precosVisiveis() } });
        document.title = `${a.name} · Maravilha`;
    };

    aoActuar = (acao, alvo) => {
        if (acao === 'opcao') {
            estado.escolha[alvo.dataset.eixo] = alvo.dataset.valor;
            const v = a.variants[indiceDaVariante(a, estado.escolha)];
            const n = v ? posicao(v.image_id) : null;
            if (n !== null) estado.imagem = n;
            desenhar();
        } else if (acao === 'imagem') {
            estado.imagem = Number(alvo.dataset.n);
            desenhar();
        } else if (acao === 'precos') {
            guardarPrecos(!precosVisiveis());
            desenhar();
        }
    };

    desenhar();
}

raiz.addEventListener('click', (evento) => {
    const alvo = evento.target.closest('[data-acao], [data-precisa-rede]');
    if (!alvo || !raiz.contains(alvo)) return;

    if (alvo.dataset.acao === 'recarregar') {
        location.reload();
    } else if (alvo.dataset.precisaRede) {
        avisar(`${alvo.dataset.precisaRede} precisa de rede.`);
    } else {
        aoActuar(alvo.dataset.acao, alvo);
    }
});

async function arrancar() {
    const caminho = location.pathname.replace(/\/+$/, '');
    const eLista = caminho === '/artigos';
    const mArtigo = caminho.match(/^\/artigos\/(\d+)$/);

    if (!eLista && !mArtigo) {
        raiz.innerHTML = appBar({ icone, voltar: { rotulo: 'Artigos', href: hrefLista() }, precos: precosVisiveis(), faixa: false }) + precisaDeRede({ icone });
        document.title = 'Precisa de rede · Maravilha';
        return;
    }

    try {
        copia = await lerCopia();
    } catch {
        copia = null;
    }

    if (!copia) {
        raiz.innerHTML = appBar({ icone, precos: false, dataCopia: '' }) + semCopia({ icone });
        document.title = 'Sem catálogo · Maravilha';
        return;
    }

    dataCopia = formatarData(copia.estado.sincronizada_em);
    if (eLista) montarLista();
    else montarArtigo(Number(mArtigo[1]));
}

arrancar();
