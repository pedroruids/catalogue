# Decisões — Catalogue

Uma entrada por decisão (formato: `.claude/reference/adr-formato.md` do JOCA). Fonte de verdade: Xavier; este ficheiro é o espelho local.

## D1: Stack da casa — Laravel 13 + Livewire 4 + Flux + Filament v5

**Data:** 2026-10-09 · **Estado:** aceite · **Decidido por:** Pedro (/start)

**Contexto:** SaaS web com backoffice de catálogo e app de consulta mobile-first, uma equipa pequena.

**Decisão:** Laravel 13 no backend, Livewire 4 + Flux na app dos vendedores, Filament v5 no backoffice.

**Alternativas consideradas:**
- Next.js 16 + API Laravel — melhor para PWA/offline — rejeitada: duas apps a manter para um produto de uma empresa só; o offline resolve-se à parte (D4).

**Consequências:** um só repositório e deploy; backoffice quase de graça. Livewire depende do servidor, por isso o offline precisa de uma camada própria (D4).

## D2: MySQL 8.4 em dev e produção, gerido por CLI

**Data:** 2026-10-09 · **Estado:** aceite · **Decidido por:** Pedro (/start)

**Contexto:** produção da casa é MySQL (cPanel/Ploi); dev e produção em motores diferentes esconde erros até ao deploy.

**Decisão:** MySQL 8.4 também em dev, instalado pelo FlyEnv nesta máquina; gestão só por CLI. CI corre MySQL.

**Alternativas consideradas:**
- PostgreSQL (já existe na máquina) — rejeitada: foge à produção da casa.
- SQLite em dev — rejeitada: ignora o modo estrito do MySQL.

**Consequências:** a E1 tem de instalar e arrancar o MySQL pelo MCP do FlyEnv (ainda não está instalado).

## D3: Uma empresa só (single-tenant)

**Data:** 2026-10-09 · **Estado:** aceite · **Decidido por:** Pedro (/start)

**Contexto:** o produto é para uma empresa de brindes (cliente novo).

**Decisão:** sem tenancy — um catálogo, uma empresa.

**Alternativas consideradas:**
- SaaS multi-empresa — rejeitada: muda o modelo de dados sem cliente que o peça.
- Single-tenant com `tenant_id` preparado — rejeitada: complexidade antecipada (YAGNI).

**Consequências:** mais simples. Passar a multi-empresa mais tarde obriga a uma migração.

## D4: Offline por PWA, só leitura

**Data:** 2026-10-09 · **Estado:** aceite · **Decidido por:** Pedro (/start)

**Contexto:** o vendedor mostra o catálogo em clientes com rede fraca ou sem rede.

**Decisão:** PWA instalável com service worker; o catálogo (dados + imagens em tamanho mobile) é descarregado para o dispositivo e consultado sem rede. Editar e partilhar exigem rede.

**Alternativas consideradas:**
- Sem offline na v1 — rejeitada: o Pedro quer offline na v1.
- Offline com edição e sincronização nos dois sentidos — rejeitada: conflitos, sem necessidade (só o admin edita).

**Consequências:** as páginas Livewire não funcionam offline. A vista offline é uma camada de cliente à parte (snapshot JSON + IndexedDB, renderizado no browser). Riscos: espaço no dispositivo e dados velhos → mostrar a data da última sincronização.

## D5: Partilha por link público com modo

**Data:** 2026-10-09 · **Estado:** aceite · **Decidido por:** Pedro (/start)

**Contexto:** o cliente quer partilhar com preços, sem preços ou só imagens.

**Decisão:** o vendedor gera um URL único (token não adivinhável) para um artigo ou uma selecção, num de três modos: com preços · sem preços · só imagens. Tem validade e pode ser revogado. Abre sem login.

**Alternativas consideradas:**
- PDF gerado — rejeitada para a v1: fica desactualizado e não se revoga.
- Download só das imagens — rejeitada: cobre só um dos três modos.

**Consequências:** o modo escolhe-se no servidor; «sem preços» nunca envia preços ao browser. Links reencaminhados continuam a funcionar até expirarem ou serem revogados.

## D6: Repositório público pedroruids/catalogue

**Data:** 2026-10-09 · **Estado:** aceite · **Decidido por:** Pedro (/start)

**Contexto:** distribuição «publicado».

**Decisão:** repo `pedroruids/catalogue`, público.

**Alternativas consideradas:**
- Privado — rejeitada pelo Pedro.

**Consequências:** nenhum segredo, dado de cliente ou export de BD entra no git (`.env` fora, seeders só com dados fictícios). Rulesets em repo público não precisam de plano pago.

## D7: Deploy e BD de produção — decidir depois

**Data:** 2026-10-09 · **Estado:** proposta · **Decidido por:** Pedro (/start)

**Contexto:** sem alvo de produção definido.

**Decisão:** só local por agora. Defaults da casa a considerar na fase final: Ploi ou cPanel.

**Consequências:** imagens em disco local na v1; S3/R2 decide-se com o deploy.

## D8: Nome visível na interface é «Maravilha»

**Data:** 2026-10-10 · **Estado:** aceite · **Decidido por:** Pedro (E2)

**Contexto:** o projecto chama-se Catalogue internamente; a app é do cliente Maravilha.

**Decisão:** app, PWA, página da partilha e emails mostram «Maravilha». «Catalogue» fica só como nome do repo/projecto.

**Alternativas consideradas:**
- «Catalogue» visível — rejeitada: a app é da marca do cliente.

**Consequências:** manifest da PWA, `APP_NAME` e textos usam «Maravilha»; o logótipo continua em falta (BRAND.md).

## D9: Tipografia Bricolage Grotesque + Figtree

**Data:** 2026-10-10 · **Estado:** aceite · **Decidido por:** Pedro (E2, mockups do Claude Design)

**Contexto:** os mockups do Claude Design trouxeram a «proposta B» com outras fontes que não as do DESIGN.md.

**Decisão:** Bricolage Grotesque nos títulos e Figtree no corpo. Substitui Fraunces + Manrope. A paleta base, o raio de 8 px e a direcção quente mantêm-se; os tokens novos dos mockups entram no sistema (DESIGN.md).

**Alternativas consideradas:**
- Fraunces + Manrope (DESIGN.md do /start) — rejeitada: o Pedro escolheu a B ao ver os ecrãs.

**Consequências:** DESIGN.md actualizado; tema da app e do Filament (issue #11) usam as fontes novas.

## D10: Valores por omissão da partilha e da recuperação

**Data:** 2026-10-10 · **Estado:** aceite · **Decidido por:** Pedro (E2, a partir dos mockups)

**Decisão:**
- Partilha nova em modo **«Sem preços»**.
- Validade da partilha: 7 · 30 · 90 dias, **omissão 7**.
- Ligação de recuperação de password: **60 min**.
- A página pública mostra quem partilhou («partilhada por <vendedor>, da Maravilha»).
- Variante sem escalões mostra **«Preço sob consulta»**.

**Consequências:** fecha a validade por omissão do PRD §11. A omissão «Sem preços» é a opção mais segura em caso de engano.

## D11: Propostas dos mockups aceites

**Data:** 2026-10-10 · **Estado:** aceite · **Decidido por:** Pedro (E2)

**Decisão:**
- Interruptor de preços **global** à sessão (lista e artigo); o filtro «Preço» desaparece com os preços escondidos.
- «desde X €» no cartão da lista quando os preços estão visíveis.
- «Juntar à selecção» no cartão da lista e na página do artigo.
- O admin também entra na app do vendedor.
- Partilha «só imagens» mostra o nome de cada artigo.
- Ao cliente final, forma neutra («Veja os artigos»), sem «tu».

**Em aberto:** quem revoga uma partilha — o mockup diz «no backoffice», mas o vendedor não tem acesso (PRD §4).
