# Design — Catalogue

**Fonte:** escolhido na página de direcções do /start (2026-10-09). Não há marca nem manual do cliente.
É uma **direcção**: a E2 transforma-a em tokens medidos e componentes, com variantes dentro dela.

## Princípio
**Mobile-first.** O vendedor usa a app sobretudo no telemóvel e no tablet, à frente do cliente.
Desenha-se primeiro a 360 px e depois alarga-se.

## Escolhas

| Eixo | Escolha |
|---|---|
| Direcção geral | Quente e humano — tons quentes, formas suaves, próximo e acolhedor |
| Tipografia | **Bricolage Grotesque** (títulos) + **Figtree** (corpo) — D9 (antes Fraunces + Manrope) |
| Paleta | Marinho clássico — marca `#1E3A5F` · acento `#C9A227` · fundo `#FAFAF7` · texto `#14202E` |
| Forma | Cantos suaves (0.5rem = 8 px); chips, interruptor e pílulas 999 px |
| Densidade | Equilibrada |

A temperatura vem dos neutros e superfícies quentes (tokens abaixo) e das imagens em destaque; as
quatro cores base não mudam.

## Tokens (medidos dos mockups B, 2026-10-10)

Fonte e tabela completa (cores, tipografia, alturas, espaçamentos, foco, ícones):
`docs/design_handoff_maravilha_app/README.md` §Design tokens — é a referência a passar para o `@theme`
e para o tema Filament. Resumo das cores acrescentadas à paleta base:

| Token | Hex |
|---|---|
| brand-hover | `#16304F` |
| surface | `#FFFFFF` |
| surface-warm | `#F3EFE6` |
| text-muted / text-muted-2 | `#5A6472` / `#4F5967` |
| border / border-soft / border-input | `#E3DED3` / `#ECE7DC` / `#CFC8BA` |
| error (texto · fundo · contorno) | `#9E2B25` · `#FBEDEA` · `#E7C1BB` |
| warn/offline (fundo · contorno) | `#F6EBC8` · `#E9D891` |
| success (texto · fundo) | `#2E6A47` · `#E6F0EA` |

Contraste medido (WCAG 2.1): muted/bg 5.74 · muted/surface-warm 5.23 · erro 6.51 · sucesso 5.51 ·
offline 13.82 · branco/sucesso 6.42. O acento `#C9A227` é só fundo do interruptor activo e anel de
foco — nunca texto sobre claro (2.31 sobre fundo, 2.42 sobre branco).

## Notas para a execução
- O protagonista é a **imagem do produto**: lista e página do artigo dão-lhe espaço.
- O interruptor «mostrar/esconder preços» tem de ser óbvio e rápido de usar à frente do cliente.
- A página pública da partilha usa a mesma linguagem, sem a navegação da app.
- Backoffice (Filament): tema Filament com as cores da marca; não precisa de ser mobile-first.
- Na conversão, dois valores soltos dos mockups passam a tokens: `#F3F5F8` (hover do chip, ListaB) → `surface-warm`; `#8A8F98` (rádio por seleccionar, CriarPartilhaB) → `border-input`.
- Sem sombras na app: só contornos de 1 px. Foco: contorno `#1E3A5F` + anel `rgba(201,162,39,.35)` de 3 px.

## Referências
Mockups B do Claude Design: `docs/design_handoff_maravilha_app/`.

## Registo de alterações
- 2026-10-10 · tipografia Fraunces + Manrope → Bricolage Grotesque + Figtree (D9); tokens neutros, erro, sucesso e offline fixados a partir dos mockups B; regra «sem sombras, só contornos».
