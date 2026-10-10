# PROGRESSO — Catalogue

> Estado partilhado do projecto. Actualizado pelo JOCA (/start · executar-projeto · /save).
> Contexto pessoal de cada colaborador vive no Brain do próprio JOCA.

## Estado actual
E1 quase fechada: scaffold, testes, CI verde, hooks e ruleset feitos; projecto **CAT-2026** no Xavier
(cliente Maravilha) com os 11 issues da v1 (#1-#11). Falta só o Laravel Boost (interactivo, corre o Pedro).
A seguir: E2 Design.

## Fases
| Fase | Estado | Prova |
|---|---|---|
| S1 Produto (PRD inicial)        | ✅ 2026-10-09 | docs/PRD.md |
| S2 Fluxos e capacidades         | ✅ 2026-10-09 | PRD §3-§6 |
| S3 Stack + ambiente local       | ✅ 2026-10-09 | PRD §8 (tabela Ambiente local) + docs/DECISIONS.md D1-D2 |
| S4 Infraestrutura               | ✅ 2026-10-09 | repo pedroruids/catalogue (público, D6) · deploy: decidir depois (D7) |
| S5 Direcção de design           | ✅ 2026-10-09 | docs/DESIGN.md |
| E1 Fundação (scaffold+CI+hooks) | ⏳ falta Boost | CI verde no PR #1 (34 testes, MySQL 8.4) · ruleset «main protegida» · Xavier CAT-2026 #1-#11 |
| E2 Design (via: <por decidir>)  | ⬜ | — |
| E3 Ponto de situação            | ⬜ | — |
| E4 Desenvolvimento (ondas)      | ⬜ | — |
| Produção                        | ⬜ | — |

## Ondas (preenchido na E4)
| Onda | Issues | Estado | Portão |
|---|---|---|---|

## Diário (mais recente primeiro)
- 2026-10-10 · Pedro · Xavier: cliente Maravilha + projecto CAT-2026 + 11 issues (a API do Xavier passou a criar clientes, D-072 do Xavier)
- 2026-10-10 · Pedro · E1: Laravel 13.35 + Livewire 4 + Flux + Filament 5.10 + Pest 5.3 em MySQL 8.4; repo público pedroruids/catalogue; PR #1 merged; ruleset na main
- 2026-10-09 · Pedro · /start: entrevista por questionário HTML; PRD, D1-D7 e DESIGN escritos
