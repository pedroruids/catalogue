# Ondas — Catalogue (Maravilha)

> Plano de implementação da v1 (E4). Issues no Xavier, projecto CAT-2026. Dependências escritas aqui e
> no corpo de cada issue (o Xavier não tem grafo de dependências).
> Ordem: primeiro o que custa mais a desfazer (modelo de dados, acesso), depois dependência técnica,
> depois agrupar o que precisa dos mesmos olhos.

## Onda 1: Dados e acesso

**Fica possível no fim:** o admin cria vendedores, tipos de produto com campos próprios e artigos, num
backoffice já com o aspecto da Maravilha.

**Issues:** #1, #2, #3, #11
**Ordem:** #1 → #2 → #3 (em série, mesmo módulo) · #11 em paralelo
**Tamanho:** #1 S · #2 M · #3 M · #11 M
**Porque paralelo:** #1-#3 tocam em migrations, `app/Models`, `app/Filament/Resources`, `tests/Feature`;
#11 em `resources/css`, fontes, `vite.config.js`, `AdminPanelProvider.php` — disjuntos.
**Branches:** `onda-1/dados` (um commit por issue) · `onda-1/tema`

**Portão de validação:**
  O que revês: o modelo de dados (tipos, campos, artigos) e os dois papéis
  Como: entrar como admin e como vendedor; criar 1 tipo com 3 campos e 1 artigo desse tipo
  Tempo: ~15 min
  Se estiver errado: uma migração — nada assenta ainda nela

## Onda 2: Catálogo completo no backoffice

**Fica possível no fim:** um artigo real com fotografias, cores/tamanhos e preços por quantidade.

**Issues:** #4, #5, #6
**Ordem:** #4 → #5 → #6 (os três mexem em `app/Filament/Resources/Articles/` — em série)
**Tamanho:** #4 M · #5 M · #6 M

**Portão de validação:**
  O que revês: um artigo verdadeiro da Maravilha carregado de ponta a ponta
  Como: carregar 1 artigo com 3 imagens, 2 eixos de variante e escalões
  Tempo: ~15 min
  Se estiver errado: variantes/escalões — a app do vendedor ainda não os lê

## Onda 3: App do vendedor

**Fica possível no fim:** o vendedor encontra e mostra artigos no telemóvel, com ou sem preços.

**Issues:** #7, #8 (os ecrãs já existem como protótipo estático em `/prototipo` — reaproveitar as views)
**Ordem:** #7 → #8
**Tamanho:** #7 M · #8 M

**Portão de validação:**
  O que revês: lista e página do artigo com dados reais, no teu telemóvel
  Como: link na rede local; interruptor de preços; 360 px sem scroll horizontal
  Tempo: ~10 min
  Se estiver errado: só views/Livewire

## Onda 4: Partilha por link

**Fica possível no fim:** enviar ao cliente um link com o modo certo (com preços · sem preços · só imagens).

**Issues:** #9
**Tamanho:** #9 L

**Portão de validação:**
  O que revês: os 3 modos, um link expirado e um revogado
  Tempo: ~10 min
  Se estiver errado: o formato dos links públicos — por isso valida-se antes de haver links enviados a clientes

## Onda 5: Offline

**Fica possível no fim:** o catálogo abre sem rede.

**Issues:** #10 — a partir em dois antes de arrancar: 10a instalável + cópia local (manifest, service
worker, snapshot) · 10b lista e artigo a partir da cópia local, sem rede
**Tamanho:** 10a M · 10b L

**Portão de validação:**
  Como: descarregar o catálogo, pôr o telemóvel em modo avião, abrir lista e artigo
  Tempo: ~10 min

## Riscos

- **Onda 1, #2/#3 — campos dinâmicos:** a forma de guardar definições e valores (tabela vs JSON) decide
  o filtro da Onda 3 e o snapshot offline da Onda 5. É por isso que vai primeiro e tem portão próprio.
- **Onda 5 — D4:** o Livewire não corre offline; a cópia local é renderizada no cliente. Se o
  pressuposto «só leitura chega» cair, a Onda 5 cresce muito.
- Por decidir no PRD §11: categorias/filtros (afecta #7) e validade dos links (afecta #9).
