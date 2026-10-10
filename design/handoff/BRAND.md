# Marca — Catalogue

**Estado:** mínimo. O cliente (Maravilha, empresa de brindes nova) **não tem marca nem manual**
(DESIGN.md, 2026-10-09). Este documento junta só o que está decidido; o resto fica marcado em falta,
e nada se inventa.

## Identidade

| Item | Valor |
|---|---|
| Cliente | Maravilha — brindes e materiais personalizados |
| Produto | Catalogue — catálogo digital do vendedor e partilha com o cliente |
| Nome visível na app | **Maravilha** (Pedro, 2026-10-10). «Catalogue» é só o nome interno do projecto/repo |
| Logótipo | TODO: asset oficial em falta. Nos mockups usa-se um espaço reservado neutro com «Maravilha» em texto, sem desenhar logótipo |
| Ícone da PWA | TODO: depende do logótipo |

## Tom

Quente e humano: próximo e acolhedor, sem formalidade (direcção escolhida no /start, DESIGN.md).

- **Português de Portugal**, sempre (nunca PT-BR): «telemóvel», «ecrã», «partilhar», «ligação».
- Frases curtas e directas. Quem usa a app está com um cliente à frente.
- Mensagens de erro dizem o que aconteceu e o que fazer a seguir, sem culpar.
- Ao cliente final (página da partilha): forma neutra («Veja os artigos»), sem «tu» (D11).

## Visual

Restrições em `DESIGN.md`. Contraste medido da paleta (WCAG 2.1, 2026-10-10):

| Par | Contraste | Uso permitido |
|---|---|---|
| texto `#14202E` sobre fundo `#FAFAF7` | 15.74 | texto corrido |
| marca `#1E3A5F` sobre fundo `#FAFAF7` | 11.00 | texto, botões |
| fundo `#FAFAF7` sobre marca `#1E3A5F` | 11.00 | texto de botão primário |
| acento `#C9A227` sobre marca `#1E3A5F` | 4.75 | texto ≥ normal (AA) |
| texto `#14202E` sobre acento `#C9A227` | 6.80 | texto sobre botão/etiqueta dourada |
| acento `#C9A227` sobre fundo `#FAFAF7` | **2.31** | ❌ nunca texto nem ícone isolado; só decoração |
