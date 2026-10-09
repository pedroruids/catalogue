# Arquitetura

Escreve-se a sério depois de os ecrãs existirem (E2/E4). Modelar em abstrato produz
tabelas elegantes que não suportam o que o ecrã precisa de mostrar.

## Modelo de dados
<pendente: E2/E4 — entidades candidatas em docs/PRD.md §6>

| Entidade | Descrição | Relações |
|---|---|---|
| User | Admin ou vendedor (starter kit) | — |

## Módulos e fronteiras
- **Backoffice** (`/admin`, Filament v5) — só admin; gere o catálogo.
- **App do vendedor** (Livewire 4 + Flux, mobile-first) — consulta e partilha.
- **Partilha pública** — páginas sem login, por token (D5).
- **Offline** — PWA só leitura, camada de cliente própria (D4); não depende de Livewire.

## Packages escolhidos
| Package | Para quê | Porque este |
|---|---|---|
| laravel/framework 13 | Base | Stack da casa (D1) |
| livewire/livewire 4 + livewire/flux 2 | App do vendedor | Starter kit Livewire (D1) |
| filament/filament 5 | Backoffice | Stack da casa (D1) |
| pestphp/pest 5 | Testes | Doutrina da casa |

## Integrações externas
Nenhuma na v1.

## Fora do Laravel padrão
<pendente: a camada offline (D4)>

## Filas e trabalho assíncrono
<pendente: provável redimensionamento de imagens e geração do snapshot offline>
