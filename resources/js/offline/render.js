/**
 * Vista offline (Onda 5b, Xavier #13): lista e página do artigo desenhadas no cliente a partir da
 * cópia local (D4 — o Livewire não corre sem rede). Funções puras: recebem dados e devolvem HTML.
 * O markup e as classes são os de resources/views/components/app/* e pages/articles/* — se um mudar,
 * o outro muda (os ecrãs offline têm de parecer os online). Nada aqui toca no DOM nem na rede.
 *
 * Ícones: o shell (resources/views/offline/shell.blade.php) leva os mesmos <x-app.icone> que as
 * páginas online, em <template>; `icone(nome, classe)` devolve o SVG como texto.
 */

const POR_PAGINA = 24;
const CHIP = 'flex h-11 shrink-0 items-center gap-1.5 rounded-full border text-sm font-semibold whitespace-nowrap';

export const esc = (valor) =>
    String(valor ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** Sem acentos e sem maiúsculas: «cafe» encontra «Café». */
export const normalizar = (texto) =>
    String(texto ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();

const euros = new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' });
export const formatarPreco = (cents) => euros.format(cents / 100);
const inteiros = new Intl.NumberFormat('pt-PT');

const colador = new Intl.Collator('pt', { sensitivity: 'base' });

/** Mesma ordem da lista online: nome, depois id. */
export function ordenar(artigos) {
    return [...artigos].sort((a, b) => colador.compare(a.name, b.name) || a.id - b.id);
}

/** Pesquisa por nome ou referência e filtro por tipo — como o `filtered()` da página online. */
export function filtrar(artigos, pesquisa = '', tipo = '') {
    const termo = normalizar(pesquisa);
    const idTipo = /^\d+$/.test(tipo) ? Number(tipo) : null;

    return ordenar(artigos).filter(
        (a) =>
            (termo === '' || normalizar(a.name).includes(termo) || normalizar(a.reference).includes(termo)) &&
            (idTipo === null || a.product_type_id === idTipo),
    );
}

/** Menor preço unitário entre as variantes activas (null: sob consulta). */
export function precoDesde(artigo) {
    const precos = artigo.variants.flatMap((v) => v.price_tiers.map((t) => t.unit_price_cents));
    return precos.length > 0 ? Math.min(...precos) : null;
}

const imagemPrincipal = (artigo) => artigo.images.find((i) => i.is_primary) ?? null;

export function etiquetaOpcoes(opcoes) {
    return Object.entries(opcoes ?? {})
        .map(([eixo, valor]) => `${eixo}: ${valor}`)
        .join(' · ');
}

/** Campos do tipo com valor: [rótulo, texto]. Vazios omitem-se; falso é um valor («Não»). */
export function camposDoArtigo(artigo, tipo) {
    const valores = artigo.field_values ?? {};
    const linhas = [];

    for (const campo of tipo?.fields ?? []) {
        const valor = valores[campo.key] ?? null;
        if (valor === null || valor === '') continue;

        let texto = String(valor);
        if (campo.data_type === 'boolean') texto = valor ? 'Sim' : 'Não';
        else if (campo.data_type === 'number' && !Number.isNaN(Number(valor))) texto = inteiros.format(Number(valor));
        linhas.push([campo.name, texto]);
    }

    return linhas;
}

/** Eixos com valores, pela ordem em que o artigo os define. */
export const eixosDoArtigo = (artigo) =>
    (artigo.variant_axes ?? []).filter((e) => (e.name ?? '') !== '' && Array.isArray(e.values) && e.values.length > 0);

/** Índice da variante que corresponde à escolha (-1: combinação indisponível). */
export const indiceDaVariante = (artigo, escolha) =>
    artigo.variants.findIndex((v) => Object.entries(escolha).every(([eixo, valor]) => v.options?.[eixo] === valor));

const BOTAO_DESACTIVADO = 'cursor-not-allowed bg-disabled text-disabled-text';

/** Barra de topo. `acoes`: {voltar:{rotulo,href}|null, precos:boolean}. Sem selecção/perfil: precisam de rede. */
export function appBar({ icone, voltar = null, precos, dataCopia, faixa = true }) {
    const esquerda = voltar
        ? `<a href="${esc(voltar.href)}" class="flex h-11 items-center gap-1 rounded-lg pl-1 pr-2.5 text-[15px] font-bold text-brand">
                ${icone('chevron-esquerda', 'size-5')}<span>${esc(voltar.rotulo)}</span></a>`
        : `<a href="/artigos" aria-label="Maravilha, lista de artigos"><div class="inline-flex h-9 items-center rounded-lg border border-dashed border-placeholder-logo px-2 font-display text-[19px] font-semibold text-brand">Maravilha</div></a>`;

    return `<header class="border-b border-border bg-surface">
        <div class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-2.5">
            ${esquerda}
            <div class="flex-1"></div>
            <button type="button" data-precisa-rede="A selecção" aria-label="Selecção, precisa de rede"
                    class="flex h-11 items-center gap-1.5 rounded-full border border-border bg-surface px-3 text-[15px] font-bold text-brand tabular-nums">
                ${icone('saco', 'size-[18px]')}
            </button>
            <button type="button" data-acao="precos" aria-pressed="${precos}"
                    aria-label="${precos ? 'Preços visíveis' : 'Preços escondidos'}" title="${precos ? 'Preços visíveis' : 'Preços escondidos'}"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full border-2 ${precos ? 'border-gold bg-gold text-text' : 'border-brand bg-surface text-brand'}">
                ${icone(precos ? 'olho' : 'olho-cortado', 'size-[18px]')}
            </button>
            <button type="button" data-precisa-rede="A minha conta" aria-label="A minha conta, precisa de rede" title="A minha conta"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full border border-border bg-surface text-brand">
                ${icone('utilizador', 'size-[18px]')}
            </button>
        </div>
        ${
            faixa
                ? `<div role="status" class="border-t border-warn-border bg-warn-bg">
            <div class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-2 text-sm font-semibold text-text">
                ${icone('sem-wifi', 'size-[18px]')}
                <span>${esc(dataCopia ? `Sem rede — a ver a cópia de ${dataCopia}` : 'Sem rede e sem catálogo guardado')}</span>
            </div>
        </div>`
                : ''
        }
        <p id="aviso-rede" role="status" aria-live="polite" hidden
           class="border-t border-border-soft bg-surface-warm px-4 py-2 text-center text-sm font-semibold text-brand"></p>
    </header>`;
}

export function estadoVazio({ icone, nome, titulo, texto = null, tom = 'neutro', accoes = '' }) {
    const cor = tom === 'erro' ? 'bg-error-bg text-error' : 'bg-surface-warm text-brand';
    return `<div class="flex flex-col items-center gap-2.5 px-4 py-12 text-center">
        <div class="flex size-16 items-center justify-center rounded-full ${cor}">${icone(nome, 'size-7')}</div>
        <h2 class="font-display text-[22px] font-semibold">${esc(titulo)}</h2>
        ${texto ? `<p class="max-w-[360px] text-[15px] leading-normal text-pretty text-muted">${esc(texto)}</p>` : ''}
        ${accoes ? `<div class="mt-1.5 flex flex-col items-center gap-2.5">${accoes}</div>` : ''}
    </div>`;
}

const BOTAO_PRIMARIO = 'flex h-12 items-center justify-center rounded-lg bg-brand px-5 text-base font-bold text-bg hover:bg-brand-hover';

/** Sem cópia local: nada para mostrar. */
export function semCopia({ icone }) {
    return estadoVazio({
        icone,
        nome: 'sem-wifi',
        tom: 'erro',
        titulo: 'Sem rede e sem catálogo guardado',
        texto: 'Este telemóvel ainda não tem o catálogo. Ligue-se à rede e, na lista de artigos, use «Descarregar» em «Catálogo no telemóvel».',
        accoes: `<button type="button" data-acao="recarregar" class="${BOTAO_PRIMARIO}">Tentar outra vez</button>`,
    });
}

/** Rotas do vendedor que precisam do servidor (partilhar, conta…). */
export function precisaDeRede({ icone }) {
    return estadoVazio({
        icone,
        nome: 'sem-wifi',
        titulo: 'Esta página precisa de rede',
        texto: 'Sem rede só se consulta o catálogo. Volte quando tiver ligação.',
        accoes: `<a href="/artigos" class="${BOTAO_PRIMARIO}">Ver artigos</a>`,
    });
}

function cartao(artigo, { icone, precos }) {
    const imagem = imagemPrincipal(artigo);
    const desde = precos ? precoDesde(artigo) : null;
    const foto = imagem
        ? `<img src="${esc(imagem.thumb)}" alt="" loading="lazy" class="aspect-square w-full bg-img-placeholder object-cover">`
        : `<div class="flex aspect-square flex-col items-center justify-center gap-2 bg-skeleton-2 p-2 text-center">${icone('foto', 'size-7 text-img-label')}<span class="text-[13px] font-semibold text-img-label">Sem imagens</span></div>`;
    const preco = !precos
        ? ''
        : desde !== null
          ? `<span class="text-sm">desde <strong class="font-bold text-brand tabular-nums">${esc(formatarPreco(desde))}</strong></span>`
          : '<span class="text-sm text-muted">Preço sob consulta</span>';

    return `<article class="relative flex min-w-0 flex-col overflow-hidden rounded-lg border border-border bg-surface">
        ${foto}
        <div class="flex flex-1 flex-col gap-1 px-3 pt-2.5 pb-3">
            <h2 class="line-clamp-2 text-[15px] leading-[1.3] font-bold text-pretty">
                <a href="/artigos/${artigo.id}" class="after:absolute after:inset-0 after:content-['']">${esc(artigo.name)}</a>
            </h2>
            <span class="text-[13px] text-muted tabular-nums">${esc(artigo.reference)}</span>
            ${preco}
            <div class="min-h-1.5 flex-1"></div>
            <button type="button" data-precisa-rede="Juntar à selecção" title="Juntar precisa de rede"
                    class="relative z-10 flex h-11 items-center justify-center rounded-lg border-[1.5px] border-disabled text-[13px] font-bold ${BOTAO_DESACTIVADO}" aria-disabled="true">Precisa de rede</button>
        </div>
    </article>`;
}

/** Corpo da lista. `copia`: {artigos, tipos}; `vista`: {pesquisa, tipo, porPagina, precos}. */
export function lista({ icone, copia, vista }) {
    const { pesquisa, tipo, porPagina, precos } = vista;
    const filtrados = filtrar(copia.artigos, pesquisa, tipo);
    const total = filtrados.length;
    const temFiltros = normalizar(pesquisa) !== '' || /^\d+$/.test(tipo);
    const tipoActual = /^\d+$/.test(tipo) ? copia.tipos.find((t) => t.id === Number(tipo)) : null;
    const tipos = [...copia.tipos].sort((a, b) => colador.compare(a.name, b.name));

    const chipTipo = tipoActual
        ? `<button type="button" data-acao="tipo" data-tipo="" aria-label="Retirar filtro Tipo: ${esc(tipoActual.name)}"
                   class="${CHIP} border-brand bg-brand pr-2.5 pl-3.5 text-bg"><span>Tipo: ${esc(tipoActual.name)}</span>${icone('fechar', 'size-4')}</button>`
        : `<button type="button" data-acao="abrir-tipos" aria-expanded="false" aria-controls="opcoes-tipo"
                   class="${CHIP} border-border-input bg-surface pr-3 pl-3.5 text-text hover:bg-surface-warm">Tipo de produto${icone('chevron-baixo', 'size-4')}</button>`;

    const filtros =
        tipos.length > 0
            ? `<div>
                <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1" role="group" aria-label="Filtros">${chipTipo}</div>
                <div id="opcoes-tipo" hidden class="mt-2 flex flex-wrap gap-2 rounded-lg border border-border bg-surface p-3">
                    ${tipos.map((t) => `<button type="button" data-acao="tipo" data-tipo="${t.id}" class="${CHIP} border-border-input bg-surface px-3.5 text-text hover:bg-surface-warm">${esc(t.name)}</button>`).join('')}
                </div>
            </div>`
            : '';

    let corpo;
    if (total > 0) {
        corpo = `<div class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">${filtrados
            .slice(0, porPagina)
            .map((a) => cartao(a, { icone, precos }))
            .join('')}</div>
            ${total > porPagina ? '<button type="button" data-acao="mais" class="mx-auto flex h-12 items-center justify-center rounded-lg border-[1.5px] border-brand bg-surface px-5 text-base font-bold text-brand hover:bg-surface-warm">Mostrar mais artigos</button>' : ''}`;
    } else if (temFiltros) {
        corpo = estadoVazio({
            icone,
            nome: 'lupa',
            titulo: 'Nenhum artigo encontrado',
            texto: 'Nada corresponde a esta pesquisa com estes filtros. Experimente outra palavra ou retire filtros.',
            accoes: `<button type="button" data-acao="limpar" class="flex h-12 items-center justify-center rounded-lg border-[1.5px] border-brand bg-surface px-5 text-base font-bold text-brand hover:bg-surface-warm">Limpar filtros</button>`,
        });
    } else {
        corpo = estadoVazio({
            icone,
            nome: 'caixa',
            titulo: 'Ainda não há artigos publicados',
            texto: 'A cópia guardada neste telemóvel não tem artigos.',
        });
    }

    const cabecalho = `<div class="flex flex-wrap items-baseline justify-between gap-3">
                <h1 class="font-display text-[28px] font-semibold">Artigos</h1>
                <span id="contagem" class="text-sm text-muted tabular-nums" aria-live="polite">${total === 1 ? '1 artigo' : `${total} artigos`}</span>
            </div>
            <div class="relative">
                ${icone('lupa', 'pointer-events-none absolute top-3.5 left-3.5 size-5 text-muted')}
                <label for="pesquisa" class="sr-only">Pesquisar artigos</label>
                <input id="pesquisa" type="search" value="${esc(pesquisa)}" placeholder="Pesquisar por nome ou referência" autocomplete="off"
                       class="h-12 w-full rounded-lg border-[1.5px] border-border-input bg-surface pr-3.5 pl-11 text-base text-text placeholder:text-muted focus:border-brand">
            </div>`;
    const resto = `${filtros}${corpo}`;

    // `resto` vive num contentor próprio para se redesenhar a cada tecla sem tirar o foco à pesquisa.
    return {
        total,
        resto,
        html: `<main class="mx-auto flex max-w-[1240px] flex-col gap-4 px-4 pt-5 pb-10">${cabecalho}<div id="lista-resto" class="flex flex-col gap-4">${resto}</div></main>`,
    };
}

/** Página do artigo. `estado`: {escolha, imagem, precos}. */
export function artigo({ icone, artigo: a, tipo, estado }) {
    const imagens = a.images;
    const variantes = a.variants;
    const eixos = eixosDoArtigo(a);
    const campos = camposDoArtigo(a, tipo);
    const indice = indiceDaVariante(a, estado.escolha);
    const img = Math.min(Math.max(estado.imagem, 0), Math.max(imagens.length - 1, 0));

    const galeria =
        imagens.length > 0
            ? `<div class="relative aspect-square overflow-hidden rounded-lg bg-img-placeholder">
                    <img src="${esc(imagens[img].screen)}" alt="${esc(a.name)}" class="size-full object-cover">
                    ${imagens.length > 1 ? `<span class="absolute right-2.5 bottom-2.5 rounded-full bg-text/75 px-2.5 py-1 text-[13px] font-semibold text-bg tabular-nums">${img + 1} / ${imagens.length}</span>` : ''}
                </div>
                ${
                    imagens.length > 1
                        ? `<div class="flex gap-2 overflow-x-auto pb-0.5" role="group" aria-label="Imagens">${imagens
                              .map(
                                  (i, n) => `<button type="button" data-acao="imagem" data-n="${n}" aria-label="Imagem ${n + 1} de ${imagens.length}" ${n === img ? 'aria-current="true"' : ''}
                                    class="size-[60px] shrink-0 overflow-hidden rounded-lg border-2 ${n === img ? 'border-brand' : 'border-border'} bg-img-placeholder"><img src="${esc(i.thumb)}" alt="" loading="lazy" class="size-full object-cover"></button>`,
                              )
                              .join('')}</div>`
                        : ''
                }`
            : `<div class="flex aspect-square flex-col items-center justify-center gap-2 rounded-lg bg-skeleton-2 p-2 text-center">${icone('foto', 'size-7 text-img-label')}<span class="text-[13px] font-semibold text-img-label">Sem imagens</span></div>`;

    const blocoEixos =
        variantes.length > 0 && eixos.length > 0
            ? `${eixos
                  .map(
                      (eixo, n) => `<div class="flex flex-col gap-2.5">
                    <span id="eixo-${n}" class="text-sm font-bold [overflow-wrap:anywhere]">${esc(eixo.name)}: <span class="font-medium">${esc(estado.escolha[eixo.name] ?? '')}</span></span>
                    <div class="flex flex-wrap gap-2" role="group" aria-labelledby="eixo-${n}">${eixo.values
                        .map((valor) => {
                            const activo = estado.escolha[eixo.name] === valor;
                            return `<button type="button" data-acao="opcao" data-eixo="${esc(eixo.name)}" data-valor="${esc(valor)}" aria-pressed="${activo}"
                                class="min-h-11 max-w-full min-w-[88px] rounded-lg px-3.5 py-2 text-[15px] border-[1.5px] [overflow-wrap:anywhere] ${activo ? 'border-brand bg-brand font-bold text-bg' : 'border-border-input bg-surface font-semibold text-text'}">${esc(valor)}</button>`;
                        })
                        .join('')}</div></div>`,
                  )
                  .join('')}
              ${indice === -1 ? '<p role="status" class="rounded-lg bg-surface-warm p-4 text-[15px] font-semibold text-brand">Esta combinação não está disponível.</p>' : ''}`
            : '';

    let blocoPrecos = '';
    if (variantes.length > 0) {
        let conteudo;
        if (!estado.precos) {
            conteudo = `<div class="flex items-center gap-3 rounded-lg bg-surface-warm p-4 text-brand">${icone('olho-cortado', 'size-[22px]')}<span class="text-[15px] font-semibold">Preços escondidos</span></div>`;
        } else if (indice === -1) {
            conteudo = '';
        } else {
            const v = variantes[indice];
            const escaloes = v.price_tiers;
            conteudo = `<div class="flex flex-col gap-2.5">
                ${Object.keys(v.options ?? {}).length > 0 ? `<span class="min-w-0 text-sm text-muted [overflow-wrap:anywhere]">${esc(etiquetaOpcoes(v.options))} · preço unitário</span>` : ''}
                ${
                    escaloes.length > 0
                        ? `<div class="overflow-hidden rounded-lg border border-border bg-surface"><table class="w-full tabular-nums" data-escaloes>
                            <thead class="bg-surface-warm text-[13px] font-bold text-muted-2"><tr>
                                <th scope="col" class="px-4 py-2.5 text-left font-bold">Quantidade mínima</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-bold">Preço unitário</th></tr></thead>
                            <tbody class="text-base">${[...escaloes]
                                .sort((x, y) => x.min_quantity - y.min_quantity)
                                .map(
                                    (t) => `<tr class="border-t border-border-soft"><td class="px-4 py-3">${inteiros.format(t.min_quantity)} un.</td>
                                    <td class="px-4 py-3 text-right font-bold text-brand">${esc(formatarPreco(t.unit_price_cents))}</td></tr>`,
                                )
                                .join('')}</tbody></table></div>`
                        : `<div class="flex flex-col gap-1 rounded-lg border border-border bg-surface p-4"><span class="text-[17px] font-bold text-brand">Preço sob consulta</span><span class="text-sm text-muted">Esta variante ainda não tem escalões de preço.</span></div>`
                }
            </div>`;
        }
        blocoPrecos = `<section class="flex flex-col gap-2.5" aria-labelledby="titulo-precos"><h2 id="titulo-precos" class="font-display text-xl font-semibold">Preço por quantidade</h2>${conteudo}</section>`;
    }

    const blocoCampos =
        campos.length > 0
            ? `<section class="flex flex-col gap-2.5" aria-labelledby="titulo-caracteristicas">
                <h2 id="titulo-caracteristicas" class="font-display text-xl font-semibold">Características</h2>
                <dl class="border-t border-border">${campos
                    .map(
                        ([rotulo, valor]) => `<div class="grid grid-cols-[minmax(110px,40%)_1fr] gap-3 border-b border-border py-[11px] text-[15px] leading-[1.45]">
                            <dt class="min-w-0 text-muted [overflow-wrap:anywhere]">${esc(rotulo)}</dt><dd class="min-w-0 text-pretty text-text [overflow-wrap:anywhere]">${esc(valor)}</dd></div>`,
                    )
                    .join('')}</dl></section>`
            : '';

    return `<main class="mx-auto max-w-[1240px] px-4 pt-4 pb-12">
        <div class="flex flex-wrap items-start gap-x-10 gap-y-6">
            <div class="flex min-w-0 flex-[1_1_440px] flex-col gap-2.5">${galeria}</div>
            <div class="flex min-w-0 flex-[1_1_340px] flex-col gap-6">
                <div class="flex flex-col gap-1.5">
                    <span class="text-[13px] font-bold tracking-[0.04em] text-muted uppercase">${esc(tipo?.name ?? '')}</span>
                    <h1 class="font-display text-[30px] leading-[1.15] font-semibold text-pretty [overflow-wrap:anywhere]">${esc(a.name)}</h1>
                    <span class="text-sm text-muted tabular-nums">Ref. ${esc(a.reference)}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex flex-wrap gap-2.5">
                        <button type="button" data-precisa-rede="Partilhar" aria-disabled="true" class="flex h-12 flex-[1_1_140px] items-center justify-center gap-2 rounded-lg px-[18px] text-base font-bold ${BOTAO_DESACTIVADO}">Partilhar</button>
                        <button type="button" data-precisa-rede="Juntar à selecção" aria-disabled="true" class="flex h-12 flex-[1_1_160px] items-center justify-center gap-2 rounded-lg px-[18px] text-base font-bold ${BOTAO_DESACTIVADO}">Juntar à selecção</button>
                    </div>
                    <span class="text-sm text-muted-2">Partilhar precisa de rede. Pode continuar a consultar o artigo.</span>
                </div>
                ${blocoEixos}
                ${blocoPrecos}
                ${blocoCampos}
            </div>
        </div>
    </main>`;
}

export function artigoNaoEncontrado({ icone }) {
    return estadoVazio({
        icone,
        nome: 'caixa',
        titulo: 'Artigo não encontrado',
        texto: 'Este artigo não está na cópia guardada neste telemóvel. Actualize o catálogo quando tiver rede.',
        accoes: `<a href="/artigos" class="${BOTAO_PRIMARIO}">Ver artigos</a>`,
    });
}

export { POR_PAGINA };
