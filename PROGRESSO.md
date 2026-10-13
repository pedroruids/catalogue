# PROGRESSO — Catalogue

> Estado partilhado do projecto. Actualizado pelo JOCA (/start · executar-projeto · /save).
> Contexto pessoal de cada colaborador vive no Brain do próprio JOCA.

## Estado actual
E4 a meio: Ondas 1-4 e 5a na `main` (Xavier #1-#12 fechados). Onda 5b (lista e artigo sem rede,
Xavier #13) no PR #16, aprovado e por fundir. Por fazer: portões humanos das Ondas 1-5 no telemóvel — há dados de demo (`DemoSeeder`,
`vendedor@maravilha.test` / `password`). Deploy por decidir (D7); a app instalável exige HTTPS fora de localhost.

## Fases
| Fase | Estado | Prova |
|---|---|---|
| S1 Produto (PRD inicial)        | ✅ 2026-10-09 | docs/PRD.md |
| S2 Fluxos e capacidades         | ✅ 2026-10-09 | PRD §3-§6 |
| S3 Stack + ambiente local       | ✅ 2026-10-09 | PRD §8 (tabela Ambiente local) + docs/DECISIONS.md D1-D2 |
| S4 Infraestrutura               | ✅ 2026-10-09 | repo pedroruids/catalogue (público, D6) · deploy: decidir depois (D7) |
| S5 Direcção de design           | ✅ 2026-10-09 | docs/DESIGN.md |
| E1 Fundação (scaffold+CI+hooks) | ✅ 2026-10-13 · Boost no PR #12 | CI verde no PR #1 (34 testes, MySQL 8.4) · ruleset «main protegida» · Xavier CAT-2026 #1-#11 |
| E2 Design (via: Claude Design)  | ✅ 2026-10-12 | docs/design_handoff_maravilha_app/ · protótipo PR #5 · tema PR #6 · D9-D11 |
| E3 Ponto de situação            | ✅ 2026-10-12 | docs/ONDAS.md |
| E4 Desenvolvimento (ondas)      | ⏳ Ondas 1-4 e 5a na main; falta 5b | PRs #7-#10, #14 |
| Produção                        | ⬜ | deploy por decidir (D7) |

## Ondas (preenchido na E4)
| Onda | Issues | Estado | Portão |
|---|---|---|---|
| 1 Dados e acesso | #1 #2 #3 #11 | ✅ 2026-10-12 · PR #7 + #6 | ⬜ por rever (admin + vendedor, 1 tipo com 3 campos) |
| 2 Catálogo no backoffice | #4 #5 #6 | ✅ 2026-10-12 · PR #8 | ⬜ por rever (1 artigo real com imagens, variantes, escalões) |
| 3 App do vendedor | #7 #8 | ✅ 2026-10-12 · PR #9 | ⬜ por rever (telemóvel, interruptor de preços, 360 px) |
| 4 Partilha por link | #9 | ✅ 2026-10-13 · PR #10 | ⬜ por rever (3 modos, link expirado e revogado) |
| 5a Offline: instalável e cópia local | #10 | ✅ 2026-10-13 · PR #14 | ⬜ por rever (instalar, descarregar, sair da conta) |
| 5b Offline: lista e artigo sem rede | #13 | ⏳ PR #16 aprovado (3 revisões), por fundir | ⬜ por rever (modo avião: lista, artigo, interruptor) |

## Diário (mais recente primeiro)
- 2026-10-13 · Pedro · Onda 5b (PR #16): lista e artigo sem rede a partir da cópia local, shell offline estático no SW, «precisa de rede» em partilhar/juntar; D18 — interruptor de preços abre sempre em «sem preços», online e offline; 3 revisões independentes (XSS, fuga de preços, fallback do SW por mutação)
- 2026-10-13 · Pedro · Laravel Boost 2.10 (PR #12, E1 fechada) · DemoSeeder (PR #13) corrido na BD local · Onda 5 partida em #10 (5a) e #13 (5b) · Onda 5a na main (PR #14): app instalável, «descarregar catálogo» para IndexedDB + Cache, actualização automática, cópia apagada no logout/troca de utilizador; 2 revisões independentes · D17 (cópia offline leva preços)
- 2026-10-13 · Pedro · Onda 4 (partilha por link, Xavier #9) na main via PR #10 (333 testes, revisão independente + gate de runtime 360 px); Xavier #9 fechado; 2 tabelas novas (`shares`, `share_article`) — `php artisan migrate` na BD local
- 2026-10-12 · Pedro · Ondas 1-3 na main: papéis, tipos de produto com campos dinâmicos e artigos (PR #7) · imagens, variantes e escalões (PR #8) · lista e página do artigo no vendedor (PR #9); tema D9 na app e no Filament (PR #6); protótipo estático (PR #5)
- 2026-10-10 · Pedro · protótipo estático no stack (Blade + Flux + Alpine, sem BD): ecrãs 1-7 com todos os estados, tokens D9 no @theme, APP_NAME Maravilha; gate de runtime independente verde (55 rotas×estados a 360 px, D5 sem «€»); 134 testes
- 2026-10-10 · Pedro · mockups B (ecrãs 1-7) recebidos e validados; D9 tipografia Bricolage + Figtree; D10 omissões; D11 propostas aceites; D8-D11 no Xavier
- 2026-10-10 · Pedro · E2 arrancada, via Claude Design: BRAND.md (mínimo; nome visível «Maravilha»; logótipo em falta), ECRAS.md (ecrãs 1-7 com 4 estados; 8-11 só tema Filament), pacote em design/handoff/. Aguarda export em design/claude-design/
- 2026-10-10 · Pedro · Xavier: cliente Maravilha + projecto CAT-2026 + 11 issues (a API do Xavier passou a criar clientes, D-072 do Xavier)
- 2026-10-10 · Pedro · E1: Laravel 13.35 + Livewire 4 + Flux + Filament 5.10 + Pest 5.3 em MySQL 8.4; repo público pedroruids/catalogue; PR #1 merged; ruleset na main
- 2026-10-09 · Pedro · /start: entrevista por questionário HTML; PRD, D1-D7 e DESIGN escritos
