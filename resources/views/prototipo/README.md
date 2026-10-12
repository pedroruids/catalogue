# Protótipo estático — API para quem constrói os ecrãs

Protótipo navegável, sem BD, só em `local` (rotas registadas em `bootstrap/app.php` → `routes/prototipo.php`).
Índice: `/prototipo`. Fonte visual: `docs/design_handoff_maravilha_app/` (README + `*B.dc.html`).

## Rotas e variáveis das views

| Rota (nome) | View | Variáveis |
|---|---|---|
| `/prototipo` (`prototipo.indice`) | `indice` | — |
| `/prototipo/login` (`prototipo.login`) | `login` | — |
| `/prototipo/recuperar` (`prototipo.recuperar`) | `recuperar` | — |
| `/prototipo/artigos` (`prototipo.artigos`) | `artigos` | `$artigos` |
| `/prototipo/artigos/{ref}` (`prototipo.artigo`) | `artigo` | `$ref`, `$artigo` (null se a ref não existe) |
| `/prototipo/partilhar` (`prototipo.partilhar`) | `partilhar` | `$seleccao` (3 artigos de exemplo) |
| `/prototipo/offline` (`prototipo.offline`) | `offline` | — |
| `/prototipo/p/{modo}` (`prototipo.publica`) | `publica` | `$modo`, `$precosPermitidos`, `$artigos`, `$vendedora` |

Todas recebem `$estado` (validado; desconhecido → o primeiro) e `$estados` (lista do ecrã, de
`PrototipoDados::ESTADOS`). Para acrescentar um estado, junta-o a `ESTADOS` — o seletor mostra-o sozinho.

Estados: login `vazio · a-carregar · erro` · recuperar `passo-1 · passo-2 · a-carregar · erro-email ·
erro-coincidem · expirada · enviado` · artigos `cheio · a-carregar · vazio · vazio-filtro · erro ·
cheio-sem-rede` · artigo `cheio · a-carregar · vazio · erro` · partilhar `form · a-carregar ·
erro-sem-rede · erro-falhou · vazio · gerada` · offline `vazio · a-carregar · interrompida · sem-espaco ·
cheio · cheio-sem-rede` · publica `cheio · a-carregar · vazio · expirada · revogada · inexistente`.

## Dados — `App\Support\PrototipoDados`

- `artigos()` — 26 artigos fictícios. Cada um: `ref` (MRV-1001…), `nome`, `tipo`, `categoria`,
  `cores` (`[{nome, hex}]`, hex da amostra — não é token), `tamanhos` (list, pode ser vazia),
  `variantes` (`[{cor, tamanho|null, escaloes: [{min, preco}]}]`, escalões vazios = «Preço sob consulta»),
  `campos` (rótulo => valor, 6-12), `imagens` (int; 0 = «Sem imagens»), `desde` (float|null).
- Casos úteis: `MRV-1005` garrafa com 2 tamanhos e `Branco|750 ml` sem escalões · `MRV-1019` e
  `MRV-1022` sem escalões nenhuns · `MRV-1012`, `MRV-1022`, `MRV-1026` sem imagens · `MRV-1013` 5 tamanhos.
- `artigo($ref)` · `euros($valor)` → `"2,10 €"` · `precosVisiveisPermitidos($modo)` (só `com-precos`).
- `artigosParaPublica($modo)` — **já sem preços** fora de `com-precos` (`desde` null, variantes sem `escaloes`).
- `VENDEDORA` = «Rita Sousa» · `VENDEDORA_EMAIL` (@example.com) · `SELECCAO_EXEMPLO` · `PARTILHA_EXEMPLO`.

## Regra dos preços (D5)

- Lista e artigo (app do vendedor): no protótipo o interruptor esconde com `x-show="$store.proto.precosVisiveis"`.
  Comentar no código que na implementação real é o servidor que não envia.
- Página pública: **o servidor remove**. Em `/p/sem-precos` e `/p/so-imagens` o HTML não pode conter
  «€» (nem em textos fixos!) — o `tests/Feature/PrototipoTest.php` verifica-o, e verifica que `com-precos` tem.

## Componentes (`<x-prototipo.*>`)

| Componente | Props | Slots / notas |
|---|---|---|
| `layout` | `titulo` · `semAppBar` (bool) · `appBar` (array de props do app-bar) · `estados` · `estado` · `semSeletor` | default = conteúdo (o ecrã põe o seu `<main>`); `cabecalho` substitui a AppBar (ex.: cabeçalho só com logótipo da página pública). Carrega fontes, `@vite(app.css, prototipo.js)`, Livewire/Alpine, Flux. Fundo `bg-bg`. |
| `app-bar` | `voltar` (rótulo, null → logótipo) · `voltarHref` (omissão: artigos) · `semPrecos` · `semSeleccao` · `sync` · `faixa` | Selecção → `prototipo.partilhar` com contagem; interruptor só-ícone (`aria-pressed`, `aria-label` «Preços visíveis/escondidos»); faixa com/sem rede pelo store. Ex.: `:app-bar="['voltar' => 'Artigos', 'semPrecos' => true]"`. |
| `logo` | `grande` (bool, 30 px no login) | «Maravilha» em caixa tracejada (provisório). |
| `cartao-artigo` | `artigo` · `href` | Imagem 1:1, nome (2 linhas), ref, «desde» (se `desde` ≠ null e preços visíveis), Juntar ↔ Na selecção, sem rede «Precisa de rede». Cartão inteiro clicável (ligação esticada); botão por cima. |
| `img-placeholder` | `etiqueta` · `ratio` (`'1/1'`, `'4/3'`, null) · `semImagens` | Riscas + «foto · etiqueta». `class` para tamanho (ex.: miniatura `ratio=null class="size-14 rounded-lg"`). |
| `estado-vazio` | `icone` · `titulo` · `texto` · `tom` (`neutro`/`erro`/`sucesso`) | default = acções (coluna ≤300 px). |
| `alerta` | `tipo` (`erro`/`aviso`/`sucesso`) · `titulo` · `icone` | default = texto. Erro com `role="alert"`. |
| `icone` | `nome` | `olho olho-cortado saco lupa mais visto partilhar chevron-esquerda chevron-baixo fechar copiar descarregar info alerta envelope relogio corrente caixa foto` (Heroicons do Flux, traço 2 px) · `sem-wifi spinner` (SVG inline). Tamanho por `class` (omissão `size-5`). |
| `seletor-estado` | `estados` · `atual` | Barra fixa no fundo (já incluída pelo layout): troca `?estado=`, simula rede, esconde-se. O layout reserva `pb-24`. |
| `toggle-rede` | — | Dentro do seletor. |

Flux: `<flux:button variant="primary">` já sai em brand; `<flux:input>`, radios, badge podem usar-se
ajustando a classes com os tokens.

## Store Alpine — `$store.proto` (`resources/js/prototipo.js`)

Ficheiro próprio (entrada do Vite) e não inline, para ser um sítio só. Persistido em localStorage
(`maravilha.proto`, com try/catch).

| Membro | Tipo | |
|---|---|---|
| `precosVisiveis` | bool (omissão true) | `togglePrecos()` |
| `seleccao` | array de refs | `juntar(ref)` · `retirar(ref)` · `toggleSeleccao(ref)` · `naSeleccao(ref)` · `limparSeleccao()` · `total` |
| `offline` | bool | `toggleRede()` · `online` (getter) · `?rede=off` / `?rede=on` na URL forçam e gravam no próximo toggle |
| `seletorAberto` | bool | `toggleSeletor()` |

Juntar/retirar não fazem nada com `offline` (D4: alterar a selecção precisa de rede).
Use `x-cloak` em tudo o que começa escondido.

## Tokens (Tailwind, `resources/css/app.css`)

Cores: `brand brand-hover gold bg surface surface-warm text muted muted-2 border border-soft border-input
placeholder-logo img-placeholder img-label skeleton skeleton-2 disabled disabled-text error error-bg
error-border warn-bg warn-border success success-bg` (→ `bg-*`, `text-*`, `border-*`).
⚠ O acento `#C9A227` é **`gold`**, não `accent`: `--color-accent` é do Flux e aponta para `brand`.
Fontes: `font-sans` (Figtree, omissão) · `font-display` (Bricolage Grotesque).
Utilitários: `bg-riscas` (imagem por haver) · `foco-marca` (o anel de foco, para casos à mão).
Raio 8 px = `rounded-lg`; pílulas `rounded-full`. Sem sombras. Foco global já aplicado (`:focus-visible`).
Larguras: `max-w-[1240px]` (lista, artigo, pública), `max-w-[1040px]` (partilhar), `max-w-[640px]` (offline), `max-w-[400px]` (login/recuperação).
