# PRD — Catalogue

**Versão:** 0.1
**Estado:** Draft
**Actualizado:** 2026-10-09
**North Star Metric:** <pendente: confirmar com o cliente — proposta: nº de partilhas abertas por clientes finais por semana>

---

## 1. Visão geral

### Problema
Uma empresa de brindes e materiais personalizados precisa de mostrar o catálogo aos clientes no
momento da visita — no telemóvel, tablet ou PC — e de o partilhar depois. A ferramenta genérica que
usa hoje não serve: não permite partilhar com e sem preços, nem partilhar só imagens.

### Solução
Plataforma web mobile-first com um catálogo digital gerido num backoffice. Cada artigo tem campos
dinâmicos por tipo de produto, variantes (cor, tamanho) e preços por escalão de quantidade. Os
vendedores consultam o catálogo (também sem rede) e partilham artigos com o cliente por link, em
três modos: com preços, sem preços ou só imagens.

### O que NÃO é
- Não faz facturação
- Não tem app móvel nativa nesta versão (é web/PWA)
- Não é multi-idioma
- Não é marketplace (uma empresa só; clientes finais não compram na plataforma)
- Não tem chat
- Não é ferramenta de BI / relatórios avançados
- Não substitui o ERP

---

## 2. Público

| Persona | Descrição | Job-to-be-done | Dor principal |
|---|---|---|---|
| P1 Admin | Responsável pelo catálogo na empresa de brindes | Quando entra um produto novo, quero inseri-lo com os campos certos para o tipo dele, para o catálogo estar sempre completo | Ferramenta actual não aceita campos diferentes por tipo nem variantes/escalões |
| P2 Vendedor | Comercial que visita clientes | Quando estou com um cliente, quero abrir o artigo certo em segundos e mostrar (ou esconder) preços, para fechar a proposta na hora | Não consegue mostrar só o que quer; rede fraca no cliente |
| P3 Cliente final (sem conta) | Empresa que encomenda brindes | Depois da visita, quero rever os artigos que me mostraram | Recebe material avulso, sem preços ou com preços que não devia ver |

Cliente: **Maravilha**, empresa de brindes nova. Uma empresa só — ver `docs/DECISIONS.md` D3.

---

## 3. Fluxos principais

### FL1 — Admin monta o catálogo
- **Quem:** Admin (P1), no backoffice.
- **Quer:** ter cada artigo completo e correcto.
- **Passos:** cria/edita um tipo de produto e os seus campos (os fixos podem ser apagados, novos
  podem ser criados) → cria o artigo nesse tipo → preenche os campos → carrega imagens → define
  variantes (cor, tamanho) → define escalões de preço por quantidade → publica.
- **Pode correr mal:** variante sem preço; escalões sobrepostos ou com buracos; imagem demasiado
  pesada para o telemóvel; apagar um campo que já tem valores em artigos existentes.

### FL2 — Vendedor mostra ao cliente
- **Quem:** Vendedor (P2), no telemóvel/tablet/PC.
- **Quer:** encontrar e mostrar o artigo certo depressa.
- **Passos:** entra → lista de artigos → filtra → abre a página do artigo → vê variantes e escalões
  → pode esconder os preços no ecrã.
- **Pode correr mal:** rede fraca (→ FL4); ecrã pequeno com muitos campos; esquecer os preços
  visíveis à frente do cliente.

### FL3 — Vendedor partilha com o cliente
- **Quem:** Vendedor (P2); destinatário Cliente final (P3), sem login.
- **Quer:** enviar ao cliente exactamente o que pode ver.
- **Passos:** escolhe um artigo ou uma selecção → escolhe o modo (com preços · sem preços · só
  imagens) → gera o link → envia (WhatsApp, email…) → o cliente abre no telemóvel, sem conta.
- **Pode correr mal:** o link é reencaminhado a terceiros; preços mudam depois da partilha; link
  esquecido continua activo (→ validade e revogação).

### FL4 — Vendedor sem rede (offline)
- **Quem:** Vendedor (P2).
- **Quer:** mostrar o catálogo onde não há internet.
- **Passos:** com rede, descarrega o catálogo para o dispositivo → sem rede, abre a app instalada
  (PWA) e consulta lista e artigos → ao voltar a rede, a cópia actualiza-se.
- **Pode correr mal:** dados velhos sem o vendedor saber (mostrar data da última sincronização);
  espaço no dispositivo por causa das imagens; tentar partilhar ou editar sem rede (só leitura).

---

## 4. Capacidades transversais

| Capacidade | Decisão |
|---|---|
| Login / contas | Email + password; recuperação de password. Papéis: **admin** e **vendedor**. Clientes finais nunca têm conta |
| Offline / sincronização | PWA, **só leitura** — ver D4 |
| Uploads / ficheiros | Imagens dos artigos (redimensionadas para mobile); armazenamento em disco local na v1, S3/R2 quando houver deploy |
| Backoffice | Filament v5 (admin) |
| Fora | multi-utilizador por empresa (tenancy), pagamentos, notificações, multi-idioma |

---

## 5. Ecrãs

**App (vendedor, Livewire, mobile-first):**
1. Login
2. Recuperação de password
3. Lista de artigos com filtro
4. Página do artigo (variantes, escalões, alternar preços)
5. Criar partilha (selecção + modo + validade) — derivado de FL3
6. Estado offline / descarregar catálogo (última sincronização) — derivado de FL4

**Público (sem login):**
7. Página da partilha (artigo ou selecção no modo escolhido) — derivado de FL3

**Backoffice (Filament):**
8. Tipos de produto e os seus campos
9. Artigos (campos, imagens, variantes, escalões)
10. Utilizadores (admin/vendedor)
11. Partilhas (listar, revogar)

---

## 6. Entidades

Entidade nomeada na entrevista: **Artigo**. As restantes saem dos fluxos e confirmam-se na E2:

| Entidade | Para quê |
|---|---|
| Artigo | O produto do catálogo |
| Tipo de produto | Define que campos um artigo tem |
| Definição de campo | Campo de um tipo (nome, tipo de dado, obrigatório, fixo/criado) |
| Imagem | Fotos do artigo (e opcionalmente de uma variante) |
| Variante | Combinação de opções do artigo (cor, tamanho…) |
| Escalão de preço | Preço por quantidade mínima, **por variante** (o preço varia por variante — Pedro, 2026-10-10) |
| Partilha | Link público: artigos, modo, validade, revogação |
| Utilizador | Admin ou vendedor |
| Categoria | <pendente: confirmar — o filtro da lista precisa de um eixo> |

---

## 7. Primeira versão (v1)

**Tudo o que está acima** — FL1 a FL4, ecrãs 1-11. Entregue por ondas na execução, mas só vai ao
cliente completo.

Distribuição: **publicado** (código público no GitHub, plataforma em produção para o cliente).

---

## 8. Stack

| Camada | Peça |
|---|---|
| Frontend web | Livewire 4 + Flux (mobile-first) |
| Backend | Laravel 13 |
| Backoffice | Filament v5 |
| Base de dados | MySQL 8.4 |
| App móvel | Nenhuma nesta versão (PWA cobre instalação e offline) |

### Ambiente local

| Máquina | SO | Ambiente | PHP | Node | BD local | Dev em |
|---|---|---|---|---|---|---|
| Pedro (única) | Ubuntu 26.04 | FlyEnv (nativo, sem Docker) | 8.4 | 22 | MySQL 8.4 (FlyEnv — a instalar na E1) | http://localhost:8000 |

Uma máquina só; `.gitattributes` com `eol=lf` entra na E1 na mesma.

---

## 9. Design

Ver `docs/DESIGN.md` — quente e humano, Bricolage Grotesque + Figtree (D9), marinho clássico, cantos suaves,
densidade equilibrada, **mobile-first**.

---

## 10. Requisitos não-funcionais

| Categoria | Requisito | Prioridade |
|---|---|---|
| Mobile | Utilizável a 360 px de largura sem scroll horizontal | P0 |
| Performance | Lista e artigo rápidos em 4G; imagens servidas em tamanhos para mobile | P0 |
| Offline | Catálogo descarregado consultável sem rede; data da última sincronização visível | P0 |
| Segurança | Link de partilha não adivinhável, com validade e revogável; o modo «sem preços» nunca envia preços ao browser | P0 |
| Acessibilidade | WCAG 2.1 AA | P1 |

---

## 11. Questões em aberto

- North Star e metas.
- Categoria para o filtro — entra? Que outros filtros (tipo, cor, preço)?
- ~~Validade por omissão dos links de partilha~~ → 7 dias (7 · 30 · 90), D10.
- Deploy e onde fica a BD de produção (D7 — decidir depois).
