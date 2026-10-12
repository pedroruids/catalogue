# Handoff: Maravilha — app do vendedor e página pública da partilha (ecrãs 1-7)

## Visão geral
Ecrãs 1-7 do `ECRAS.md` (PRD Catalogue v0.1): login, recuperação de password, lista de artigos,
página do artigo, criar partilha, catálogo offline e página pública da partilha. Cada ecrã foi
desenhado nos seus estados (vazio · a carregar · erro · cheio, mais variantes) a 360, 768 e 1280 px.
Os ecrãs 8-11 (Filament) não estão incluídos: levam só o tema com os tokens abaixo.

Proposta escolhida: **B** — Bricolage Grotesque (títulos) + Figtree (corpo).

## Sobre os ficheiros
Os ficheiros deste pacote são **referências de design feitas em HTML**: protótipos que mostram o
aspecto e o comportamento pretendidos, não código de produção. A tarefa é **recriá-los no stack do
projecto** — Laravel 13 + Livewire 4 + Flux (mobile-first), com os padrões e componentes Flux já
estabelecidos — e não copiar o HTML.

- Abrir `Maravilha Ecras B (standalone).html` num browser: tudo num ficheiro, sem servidor.
- Os `*B.dc.html` são a fonte de cada ecrã (estilos inline + uma pequena classe JS por ecrã com o
  estado). Precisam de servidor local (`php -S localhost:8080` na pasta) para abrir individualmente.
  O prop `estado` de cada ficheiro selecciona o estado.

## Fidelidade
**Alta fidelidade.** Cores, tipografia, espaçamentos, raios e textos são finais (português de
Portugal). Recriar com rigor usando Flux; onde um componente Flux já cobre o caso (input, button,
radio cards, badge), adaptá-lo aos tokens em vez de o reconstruir.

Imagens de produto são espaços reservados (riscas diagonais com a etiqueta «foto · …»). O logótipo
ainda não existe: usar o texto «Maravilha» numa caixa com contorno tracejado até haver asset.

---

## Design tokens

### Cores
| Token | Hex | Uso |
|---|---|---|
| brand | `#1E3A5F` | botões primários, ligações, estados seleccionados, títulos de marca |
| brand-hover | `#16304F` | hover do botão primário |
| accent | `#C9A227` | **só** interruptor «com preços» activo (fundo) e anel de foco (35 % alpha). Nunca texto sobre o fundo claro (2.31:1) |
| bg | `#FAFAF7` | fundo da app |
| surface | `#FFFFFF` | cartões, barra de topo, inputs |
| surface-warm | `#F3EFE6` | faixa de sincronização, cabeçalho de tabelas, chips de informação, avisos neutros |
| text | `#14202E` | texto corrido |
| text-muted | `#5A6472` | texto secundário (5.74:1 sobre bg) |
| text-muted-2 | `#4F5967` | texto secundário mais forte, botão desactivado |
| border | `#E3DED3` | contornos de cartões, separadores |
| border-soft | `#ECE7DC` | separadores internos |
| border-input | `#CFC8BA` | contorno de inputs, chips, opções não seleccionadas |
| placeholder-logo | `#B9B2A3` | contorno tracejado do logótipo |
| img-placeholder | `#EFEAE0` + riscas `rgba(20,32,46,.035)` a 135° | fundo das imagens |
| img-label | `#6B6458` | etiqueta dentro do espaço da imagem |
| skeleton | `#ECE7DC` / `#F1EDE4` | esqueletos (dois tons) |
| disabled | `#E4E0D7` fundo / `#4F5967` texto | botões desactivados |
| error | `#9E2B25` texto/contorno · `#FBEDEA` fundo · `#E7C1BB` contorno do alerta | erros |
| warn/offline | `#F6EBC8` fundo · `#E9D891` contorno · texto `#14202E` | faixa «Sem rede», aviso «Partilhar precisa de rede» |
| success | `#2E6A47` · `#E6F0EA` fundo | sincronizado, ligação pronta, «Copiada» |

Cores das variantes (amostras) vêm dos dados do artigo, não são tokens.

### Tipografia
Google Fonts: `Bricolage Grotesque` (opsz 12..96, 500/600/700) e `Figtree` (400/500/600/700).

| Papel | Fonte | Tamanho / peso |
|---|---|---|
| Título de ecrã (h1) | Bricolage Grotesque | 28 px / 600 (artigo e página pública: 30 px, line-height 1.15) |
| Título de secção / estados vazios | Bricolage Grotesque | 20–22 px / 600 |
| Logótipo provisório | Bricolage Grotesque | 19 px (barra) · 30 px (login) / 600 |
| Corpo | Figtree | 15–16 px / 400–500, line-height 1.5 |
| Rótulos de campo | Figtree | 14 px / 600 |
| Botões | Figtree | 16 px / 700 (CTA principal 17 px; chips e botões de cartão 14 px) |
| Sobretítulo (categoria, «Passo 1 de 2») | Figtree | 13 px / 700, maiúsculas, letter-spacing .04em |
| Referência / meta | Figtree | 13–14 px, `text-muted` |
Números (preços, quantidades, MB) com `font-variant-numeric: tabular-nums`.

### Forma e espaçamento
- Raio: **8 px** (cartões, inputs, botões, tabelas). Chips, interruptor e pílulas: 999 px. Amostras de cor: círculo.
- Alturas: input 48 · botão 48 (CTA principal 52) · chip de filtro 40 · alvos de toque mínimos 44×44.
- Espaçamento base 4 px; usados 6/8/10/12/16/20/24/28/40.
- Página: padding lateral 16 px; conteúdo `max-width` 1240 px (lista, artigo, pública), 1040 px (criar partilha), 640 px (offline), 400 px (login/recuperação).
- Sombras: nenhuma na app (só contornos 1 px). Foco: contorno `#1E3A5F` + `box-shadow: 0 0 0 3px rgba(201,162,39,.35)`.

### Ícones
Traço 2 px, `currentColor`, 18–20 px, cantos arredondados (estilo Lucide). Usados: olho / olho
cortado, saco (selecção), lupa, sem-wifi, mais, visto, partilhar, chevron esquerda/baixo, fechar,
copiar, descarregar, informação, alerta, envelope, relógio, corrente (ligação).

---

## Componente partilhado: barra de topo (AppBar)
Usada nos ecrãs 3-6. Fundo `#FFFFFF`, contorno inferior `#E3DED3`.

**Linha principal** (padding 10 × 16, gap 8, `max-width` 1240):
- Esquerda: logótipo provisório **ou** botão voltar («‹ Artigos», 15/700 `brand`, 44 px alto).
- Direita: botão Selecção (pílula, contorno `#E3DED3`, ícone saco + contagem, 44 px, `aria-label="Selecção"`) → ecrã 5.
- **Interruptor de preços** (só ecrãs 3 e 4): botão redondo 44×44, **só ícone**.
  - Activo: fundo e contorno 2 px `#C9A227`, ícone olho `#14202E`, `aria-pressed="true"`, `aria-label="Preços visíveis"`.
  - Inactivo: fundo `#FFFFFF`, contorno 2 px `#1E3A5F`, ícone olho cortado `#1E3A5F`, `aria-pressed="false"`, `aria-label="Preços escondidos"`.
  - Um toque alterna. O estado é **global** à sessão (lista e artigo partilham-no) e deve persistir entre navegações.

**Faixa de estado** (por baixo):
- Com rede: fundo `#F3EFE6`, 13 px `#4F5967`, ponto verde 8 px + «Ligado · catálogo sincronizado hoje, 09:12».
- Sem rede: fundo `#F6EBC8`, 14/600 `#14202E`, ícone sem-wifi + «Sem rede — a ver a cópia de 08/10/2026, 18:40».

---

## Ecrãs

### 1. Login (`LoginB.dc.html`)
Coluna centrada, `max-width` 400, vertical-center. Logótipo provisório acima de um cartão (padding 28 × 24, gap 20).
- Título «Entrar»; texto «Use o email e a password da sua conta.»
- Campos Email (placeholder `nome@example.com`) e Password; botão primário «Entrar» largura total; ligação «Esqueci-me da password» centrada.
- **A carregar:** campos `disabled`, botão desactivado com spinner + «A entrar…».
- **Erro:** alerta no topo do cartão (fundo `#FBEDEA`, texto `#9E2B25` 15/600, ícone): «Email ou password errados. Confirme e tente outra vez.» Campo password com contorno 2 px `#9E2B25`, `aria-invalid`, texto «Verifique a password. Atenção às maiúsculas.»
- **Cheio:** não existe; com sucesso → lista.

### 2. Recuperação de password (`RecuperacaoB.dc.html`)
Mesma moldura do login. Sobretítulo «Passo 1 de 2» / «Passo 2 de 2».
- **Passo 1:** «Recuperar password» · «Indique o email da conta. Enviamos uma ligação para definir uma password nova.» · Email · «Enviar ligação» · ligação «Voltar a entrar».
- **Passo 2:** «Nova password» · «Pelo menos 8 caracteres.» · Nova password · Confirmar password · «Guardar password».
- **A carregar:** «A enviar…» com spinner.
- **Erro email:** «O email parece incompleto. Exemplo: nome@example.com».
- **Erro não coincidem:** no campo de confirmação: «As passwords não coincidem. Escreva a mesma nos dois campos.»
- **Erro ligação expirada:** ícone relógio em círculo `#FBEDEA` · «Esta ligação já expirou» · «As ligações valem 60 minutos. Peça uma nova e use-a logo.» · «Pedir nova ligação». 60 min (D10).
- **Cheio:** ícone envelope em círculo verde · «Veja o seu email» · «Se o email existir, enviámos uma ligação.» · «Procure também no spam. A ligação vale 60 minutos.» · botão secundário «Voltar a entrar». Nunca revelar se a conta existe.

### 3. Lista de artigos (`ListaB.dc.html`)
AppBar + `main` (padding 20/16/40, gap 16).
- Cabeçalho: «Artigos» + contagem «24 artigos» (14 px muted) à direita.
- Pesquisa: input 48 px com lupa à esquerda, placeholder «Pesquisar por nome ou referência».
- Filtros: linha de chips com scroll horizontal (sem quebra), sangrando até às margens. Chips: «Categoria», «Tipo de produto», «Cor», «Preço» com chevron. Activo: fundo `#1E3A5F`, texto claro, ícone fechar (ex.: «Tipo: Têxtil»). **«Preço» desaparece quando os preços estão escondidos.** ⚠ Eixos dos filtros por confirmar (PRD §11).
- Grelha: `grid-template-columns: repeat(auto-fill, minmax(150px, 1fr))`, gap 12 → 2 colunas a 360, ~4 a 768, ~7 a 1280.
- **Cartão:** fundo branco, contorno, raio 8. Imagem 1:1 (a protagonista). Corpo padding 10/12/12, gap 4: nome 15/700 (máx. 2 linhas, reticências) · referência 13 px muted · «desde **2,10 €**» (só com preços; valor 700 `brand`) · botão 44 px no fundo do cartão:
  - «+ Juntar» (contorno `brand`) → «✓ Na selecção» (fundo `brand`). Alterna; actualiza a contagem na AppBar.
  - Sem rede: botão desactivado «Precisa de rede».
  - Toque no cartão (fora do botão) → página do artigo.
- **Vazio (catálogo):** ícone caixa · «Ainda não há artigos no catálogo» · «Quando o admin publicar artigos no backoffice, aparecem aqui.» Pesquisa e filtros desactivados.
- **Vazio (filtro):** «Nenhum artigo encontrado» · «Nada corresponde a «lanyard» com estes filtros. Experimente outra palavra ou retire filtros.» · botão secundário «Limpar filtros».
- **A carregar:** 8 cartões esqueleto na mesma grelha.
- **Erro (sem rede e sem cópia):** faixa offline «Sem rede e sem catálogo descarregado» · ícone sem-wifi em círculo `#FBEDEA` · «Não foi possível carregar os artigos» · «Está sem rede e o catálogo ainda não foi descarregado neste dispositivo.» · «Tentar outra vez» + ligação «Como usar sem rede» (→ ecrã 6).
- **Cheio sem rede:** faixa offline; «Juntar» desactivado.

### 4. Página do artigo (`ArtigoB.dc.html`)
AppBar com «‹ Artigos». Duas colunas com `flex-wrap` (gap 24 × 40): galeria `flex: 1 1 440px` e informação `flex: 1 1 340px` → empilha a 360 e 768, lado a lado a 1280.
- **Galeria:** imagem principal 1:1 com contador «1 / 6» (pílula `rgba(20,32,46,.75)`, canto inferior direito); miniaturas 60×60 em linha com scroll, a activa com contorno 2 px `brand`. Mudar de cor muda a imagem quando existe imagem da variante.
- **Informação** (gap 24): sobretítulo «Garrafas e copos» · nome (h1) · «Ref. MRV-1005».
- Acções: «Partilhar» (primário, ícone) e «+ Juntar à selecção» (secundário → «✓ Na selecção»), lado a lado com wrap.
  - Sem rede: ambos desactivados + «Partilhar precisa de rede. Pode continuar a consultar o artigo.»
- **Cor:** «Cor: Marinho»; amostras 34 px num alvo 44 px; seleccionada com anel 2 px `brand` (40 px) à volta de uma amostra 30 px. `aria-pressed`.
- **Tamanho:** botões segmentados (min 88 × 44); seleccionado fundo `brand`.
- **Preço por quantidade** (h2): legenda «Marinho, 500 ml · preço unitário»; tabela com cabeçalho `#F3EFE6` («Quantidade mínima» | «Preço unitário»), linhas 12 × 16, preço à direita 700 `brand`. Escalões **por variante** — muda com cor e tamanho.
  - Preços escondidos: a tabela é substituída por um painel `#F3EFE6` com olho cortado + «Preços escondidos». Nenhum preço no ecrã.
  - Variante sem escalões: painel branco «Preço sob consulta» · «Esta variante ainda não tem escalões de preço.»
- **Características** (h2): lista rótulo/valor (`dl`), grelha `minmax(110px, 40%) 1fr`, linhas 11 px com separador; pode ter 12+ campos (dinâmicos por tipo de produto).
- **Vazio:** imagem substituta `#F1EDE4` com ícone e «Sem imagens»; preço sob consulta.
- **A carregar:** esqueleto da galeria e da coluna.
- **Erro:** «Este artigo já não está no catálogo» · «Pode ter sido retirado ou a ligação estar errada.» · «Voltar aos artigos».

### 5. Criar partilha (`CriarPartilhaB.dc.html`)
AppBar com «‹ Artigos», sem interruptor de preços. Título «Partilhar com o cliente». Duas colunas com wrap (`flex: 1 1 360px` cada).
- **Artigos:** «3 artigos»; lista em cartão — miniatura 56 px, nome (1 linha, reticências), referência, botão fechar 44 px «Retirar …». Retirar o último → estado vazio.
- **O que o cliente vê** (`radiogroup`): três cartões-rádio — «Com preços» (Artigos, variantes e preços por quantidade.) · «Sem preços» (Artigos e variantes. Nenhum preço é enviado.) · «Só imagens» (Galeria com o nome de cada artigo.). Seleccionado: contorno 2 px `brand` e rádio cheio. **Por omissão: «Sem preços».**
- **Validade:** segmentado 7 dias · 30 dias · 90 dias (**omissão 7 dias**) + «Expira a 17/10/2026. Pode revogá-la antes no backoffice.» ⚠ TODO: o vendedor não tem backoffice (PRD §4) — quem revoga?
- CTA «Gerar ligação» (52 px).
- **A carregar:** CTA desactivado com spinner «A gerar ligação…»; botões retirar desactivados.
- **Erro sem rede:** aviso `#F6EBC8` «**Partilhar precisa de rede.** A selecção fica guardada; gere a ligação quando tiver rede.» + CTA desactivado; faixa offline na AppBar.
- **Erro falhou:** alerta vermelho «Não foi possível gerar a ligação. Tente outra vez; a selecção mantém-se.» + CTA «Tentar outra vez».
- **Vazio:** ícone saco · «Ainda não escolheu artigos» · «Na lista ou na página de um artigo, toque em «Juntar» para o trazer para aqui.» · «Ver artigos».
- **Cheio (ligação gerada):** cartão com visto verde · «Ligação pronta» · pílulas (modo em `brand`, «Válida até 17/10/2026», «3 artigos») · campo só-leitura com o URL + «Copiar» → «✓ Copiada» (fundo `#2E6A47`, `aria-live`) · «Enviar por»: WhatsApp · Email · Mais… (Web Share API) · ligação «Fazer outra partilha».

### 6. Catálogo no dispositivo / offline (`OfflineB.dc.html`)
AppBar com «‹ Artigos», sem interruptor. `max-width` 640. Título «Catálogo no dispositivo» · «Para mostrar artigos onde não há rede.»
Cartão de estado: ícone 44 px em círculo (verde ok · neutro `#F3EFE6` · vermelho erro) + título + subtítulo; barra de progresso opcional (10 px, `#ECE7DC` / `brand`, `role="progressbar"`); lista de factos (`dl`, valores 700 à direita); botão.
| Estado | Título · subtítulo | Factos | Botão |
|---|---|---|---|
| Vazio | Ainda não descarregou o catálogo · Use rede Wi-Fi, se puder: as imagens ocupam espaço. | Artigos 124 · Tamanho aprox. 186 MB · Espaço livre 2,3 GB | primário «Descarregar catálogo» |
| A carregar | A descarregar… · Pode continuar a usar a app. Não feche o browser. | progresso 62 % «Imagens · 77 de 124 artigos» / «116 de 186 MB» | secundário «Cancelar» |
| Erro interrompida | A descarga foi interrompida · A rede caiu a meio. O que já veio fica guardado. | progresso 41 % · Última cópia completa | primário «Retomar descarga» |
| Erro espaço | Sem espaço no dispositivo · Faltam cerca de 120 MB. Liberte espaço e tente outra vez. | Precisa de 186 MB · Espaço livre 64 MB | primário «Tentar outra vez» |
| Cheio | Catálogo descarregado · Actualiza-se sozinho quando há rede. | Última sincronização hoje, 09:12 · Artigos · Tamanho | secundário «Actualizar agora» |
| Cheio sem rede | A usar a cópia do dispositivo · Quando voltar a rede, a cópia actualiza-se. | Última sincronização 08/10/2026, 18:40 | desactivado «Actualizar precisa de rede» |
Nota fixa por baixo (fundo `#F3EFE6`): «Sem rede, pode consultar a lista e os artigos. Partilhar e juntar à selecção precisam de rede.»

### 7. Página da partilha — pública (`PartilhaPublicaB.dc.html`)
Sem navegação da app. Cabeçalho branco só com o logótipo provisório; rodapé `#F3EFE6` «Maravilha · brindes e materiais personalizados».
- Intro: «Veja os artigos» (30 px) · «Selecção partilhada por **Rita Sousa**, da Maravilha.» · pílulas «5 artigos» e «Disponível até 17/10/2026».
- **Com preços / Sem preços:** grelha `repeat(auto-fill, minmax(280px, 1fr))`, gap 16. Cartão: imagem 4:3 · nome (Bricolage 20/600) · «Ref. …» · amostras 22 px + texto de variantes («4 cores · 500 ml e 750 ml»). Com preços: «Preço unitário · Marinho, 500 ml» + tabela «desde 50 un. → 7,90 €».
- **Só imagens:** grelha `minmax(160px, 1fr)`, 2 imagens por artigo, legenda com o nome (14/600).
- **A carregar:** esqueleto da intro e de 3 cartões.
- **Vazio:** «Estes artigos já não estão disponíveis» · «Saíram do catálogo depois de serem partilhados. Fale com a Maravilha para ver alternativas.»
- **Erros** (sem dados, sem intro): expirada «Esta ligação expirou» · «Peça a quem lha enviou uma ligação nova.» — revogada «Esta ligação já não está activa» · «Quem a partilhou desactivou-a. Se precisar, peça uma ligação nova.» — inexistente «Não encontrámos esta ligação» · «Confirme se a copiou completa, até ao fim.»
- **Regra D5 (segurança):** em «sem preços» e «só imagens», nenhum preço pode chegar ao browser — nem no HTML, nem no JSON, nem em propriedades públicas do Livewire. Filtrar no servidor.

---

## Comportamento e estado
- `precosVisiveis` (bool, sessão/localStorage) — interruptor global da AppBar; afecta lista (preço no cartão, filtro de preço) e artigo (tabela). Com `false`, não renderizar preços (melhor ainda: não os enviar).
- `seleccao` (lista de IDs de artigo) — partilhada entre lista, artigo e criar partilha; contagem na AppBar. Requer rede para alterar (D4).
- `online` (`navigator.onLine` + eventos) — controla a faixa da AppBar e desactiva Partilhar/Juntar/Actualizar.
- Artigo: `corSeleccionada`, `tamanhoSeleccionado` → variante → escalões; `imagemActiva`.
- Criar partilha: `modo` (com|sem|imagens, omissão sem), `validadeDias` (7|30|90, omissão 7), `estado` (form|a gerar|gerada|erro), `copiada`.
- Offline (PWA): estado da cópia (nenhuma|a descarregar n/total|interrompida|completa), `ultimaSincronizacao`, tamanho estimado, espaço livre (`navigator.storage.estimate()`).
- Transições: nenhuma animação definida; usar as transições por omissão do Flux. Spinner estático no protótipo → usar o do Flux.
- Responsivo: tudo fluido por `flex-wrap`/`grid auto-fill`; sem media queries específicas. Verificar 360 px sem scroll horizontal (excepto a linha de chips, que tem scroll próprio).

## Em aberto (do PRD/ECRAS)
- Eixos dos filtros (categoria, tipo, cor, preço) — por confirmar.
- Página pública com preços: preços actuais ou do momento da partilha.
- Domínio real das ligações (`maravilha.pt/p/…` é exemplo).
- Logótipo e ícone da PWA.

## Assets
Nenhum asset real. Imagens são espaços reservados; ícones desenhados inline (equivalentes Lucide/Heroicons servem). Fontes via Google Fonts.

## Ficheiros
- `Maravilha Ecras B (standalone).html` — todos os ecrãs × estados × larguras, offline.
- `Maravilha Ecras B.dc.html` — a mesma tela (precisa de `support.js` e servidor local).
- `AppBarB.dc.html`, `LoginB.dc.html`, `RecuperacaoB.dc.html`, `ListaB.dc.html`, `ArtigoB.dc.html`, `CriarPartilhaB.dc.html`, `OfflineB.dc.html`, `PartilhaPublicaB.dc.html` — fonte de cada ecrã.
- `support.js` — runtime dos protótipos (não é para produção).
