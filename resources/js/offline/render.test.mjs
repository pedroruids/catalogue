// Testes da vista offline (funções puras): `node --test resources/js/offline/render.test.mjs`
import assert from 'node:assert/strict';
import test from 'node:test';
import { artigo, camposDoArtigo, esc, filtrar, indiceDaVariante, lista, precoDesde } from './render.js';

const icone = (nome) => `<svg data-i="${nome}"></svg>`;

const A = (id, name, extra = {}) => ({
    id, name, reference: `REF-${id}`, product_type_id: 1, description: null, field_values: {}, variant_axes: [],
    images: [], variants: [], ...extra,
});
const tier = (min, cents) => ({ min_quantity: min, unit_price_cents: cents });

test('esc neutraliza HTML', () => {
    assert.equal(esc(`<img src=x onerror="a">'&`), '&lt;img src=x onerror=&quot;a&quot;&gt;&#39;&amp;');
});

test('filtrar: sem acentos, por nome ou referência, e por tipo; ordena por nome', () => {
    const artigos = [A(2, 'Caneta', { product_type_id: 2 }), A(1, 'Café Mug'), A(3, 'Bloco', { reference: 'XYZ-9' })];
    assert.deepEqual(filtrar(artigos).map((a) => a.id), [3, 1, 2]);
    assert.deepEqual(filtrar(artigos, 'CAFE').map((a) => a.id), [1]);
    assert.deepEqual(filtrar(artigos, 'xyz').map((a) => a.id), [3]);
    assert.deepEqual(filtrar(artigos, '', '2').map((a) => a.id), [2]);
    assert.deepEqual(filtrar(artigos, '', 'abc').map((a) => a.id), [3, 1, 2]); // tipo forjado = sem filtro
});

test('precoDesde é o menor escalão de todas as variantes; sem escalões é null', () => {
    const v = (...t) => ({ id: 1, options: {}, image_id: null, price_tiers: t });
    assert.equal(precoDesde(A(1, 'x', { variants: [v(tier(1, 125), tier(5, 99)), v(tier(1, 300))] })), 99);
    assert.equal(precoDesde(A(1, 'x', { variants: [v()] })), null);
});

test('lista: sem preços nada de preço no HTML; com preços «desde»; sempre «Precisa de rede» no Juntar', () => {
    const artigos = [A(1, 'Caneta', { variants: [{ id: 1, options: {}, image_id: null, price_tiers: [tier(1, 125)] }] })];
    const vista = { pesquisa: '', tipo: '', porPagina: 24 };
    const sem = lista({ icone, copia: { artigos, tipos: [] }, vista: { ...vista, precos: false } }).html;
    const com = lista({ icone, copia: { artigos, tipos: [] }, vista: { ...vista, precos: true } }).html;
    assert.ok(!/desde|1,25/.test(sem));
    assert.match(com, /desde <strong[^>]*>1,25\s€/);
    assert.match(sem, /Precisa de rede/);
});

test('lista: nome malicioso é escapado e filtro sem resultados mostra «Limpar filtros»', () => {
    const copia = { artigos: [A(1, '<script>x</script>')], tipos: [] };
    const html = lista({ icone, copia, vista: { pesquisa: '', tipo: '', porPagina: 24, precos: false } }).html;
    assert.ok(!html.includes('<script>x'));
    const vazio = lista({ icone, copia, vista: { pesquisa: 'zzz', tipo: '', porPagina: 24, precos: false } });
    assert.equal(vazio.total, 0);
    assert.match(vazio.html, /Limpar filtros/);
});

test('artigo: escalões da variante escolhida; combinação inexistente avisa; preços escondidos não deixam preço', () => {
    const a = A(1, 'Caneta', {
        variant_axes: [{ name: 'Cor', values: ['Azul', 'Verde'] }],
        variants: [
            { id: 1, options: { Cor: 'Azul' }, image_id: null, price_tiers: [tier(500, 99), tier(100, 125)] },
            { id: 2, options: { Cor: 'Verde' }, image_id: null, price_tiers: [] },
        ],
    });
    const base = { icone, artigo: a, tipo: { name: 'Canetas', fields: [] } };
    const azul = artigo({ ...base, estado: { escolha: { Cor: 'Azul' }, imagem: 0, precos: true } });
    assert.ok(azul.indexOf('100 un.') > -1 && azul.indexOf('100 un.') < azul.indexOf('500 un.')); // por quantidade crescente
    assert.match(artigo({ ...base, estado: { escolha: { Cor: 'Verde' }, imagem: 0, precos: true } }), /Preço sob consulta/);
    assert.match(artigo({ ...base, estado: { escolha: { Cor: 'Roxo' }, imagem: 0, precos: true } }), /Esta combinação não está disponível/);
    const escondido = artigo({ ...base, estado: { escolha: { Cor: 'Azul' }, imagem: 0, precos: false } });
    assert.match(escondido, /Preços escondidos/);
    assert.ok(!/1,25|0,99|un\./.test(escondido));
    assert.equal(indiceDaVariante(a, { Cor: 'Verde' }), 1);
});

test('campos: vazios omitem-se, falso é «Não», números em pt-PT', () => {
    const tipo = { fields: [
        { key: 'a', name: 'A', data_type: 'text' }, { key: 'b', name: 'B', data_type: 'boolean' },
        { key: 'c', name: 'C', data_type: 'number' }, { key: 'd', name: 'D', data_type: 'text' },
    ] };
    const campos = camposDoArtigo(A(1, 'x', { field_values: { a: '', b: false, c: 1500.5 } }), tipo);
    assert.deepEqual(campos, [['B', 'Não'], ['C', '1500,5']]);
});
