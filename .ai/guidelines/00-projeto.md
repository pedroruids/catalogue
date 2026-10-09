# Contexto do projeto

<!--
Este ficheiro é TEU e vai para o git. O Laravel Boost inclui o que estiver em `.ai/guidelines/`
no CLAUDE.md que gera — nunca editar o CLAUDE.md à mão. Manter curto.
-->

## O que é

Catálogo digital mobile-first para uma empresa de brindes personalizados: o admin monta o catálogo
no backoffice (campos dinâmicos por tipo, variantes, preços por escalão) e os vendedores mostram-no
aos clientes — também sem rede — e partilham-no por link (com preços, sem preços ou só imagens).

Detalhe em `docs/PRD.md`.

## Comandos

- Testes: `./vendor/bin/pest` · um só: `--filter=<nome>`
- Formatação: `./vendor/bin/pint` · Análise: `./vendor/bin/phpstan analyse --memory-limit=1G`
- Dev: `composer run dev` (servidor em http://localhost:8000)

## Ambiente local

| Máquina / SO | Ambiente | BD local | URL de dev |
|---|---|---|---|
| Linux (Ubuntu 26.04) | FlyEnv (PHP nativo, sem Docker) | MySQL 8.4 (instância própria) | http://localhost:8000 |

- **O motor de BD local é o mesmo da produção** (MySQL 8.4, D2). Testes também correm em MySQL.
- Versões: PHP 8.4 · Node 22.
- Máquina nova: acrescenta a tua linha, não substituas a de ninguém.
- **Sem paths pessoais nem portas de terceiros aqui** — isso vive na memória do JOCA de cada um.
- Repo **público** (D6): nenhum segredo, dado real de cliente nem export de BD no git.

## Documentos de referência

- `docs/PRD.md` — o problema, as fronteiras, os fluxos
- `docs/DESIGN.md` — direcção visual (obrigatório antes de mexer em views)
- `docs/ARCHITECTURE.md` — modelo de dados, módulos, packages
- `docs/DECISIONS.md` — porquê das decisões estruturantes
- `REVIEW.md` — critérios de revisão
