# Ecrãs — Catalogue

> **Mockups (2026-10-10):** `docs/design_handoff_maravilha_app/` — proposta B, ecrãs 1-7 com todos os estados. Abrir `Maravilha Ecras B (standalone).html`.

Lista de ecrãs da v1, tirada do PRD §5 (fluxos FL1-FL4). Cada ecrã tem propósito e os **quatro
estados** (vazio · a carregar · erro · cheio). Desenha-se a **360 px primeiro**, depois tablet (768 px)
e PC (1280 px).

Os ecrãs 1-7 desenham-se no Claude Design. Os 8-11 são Filament: **não se desenham** — levam só o
tema com as cores e as fontes da marca (issue #11).

---

## App do vendedor (Livewire, mobile-first)

### 1. Login
- **Propósito:** o vendedor (ou o admin, D11) entra com email e password.
- **Vazio:** formulário limpo, com a ligação «Esqueci-me da password».
- **A carregar:** botão «Entrar» desactivado e com indicador, campos bloqueados.
- **Erro:** «Email ou password errados» junto ao formulário; campo inválido marcado e com texto.
- **Cheio:** n/a. Com sucesso, vai para a lista de artigos.

### 2. Recuperação de password
- **Propósito:** pedir a ligação por email e depois definir a nova password (dois passos).
- **Vazio:** campo de email; no 2.º passo, nova password + confirmação.
- **A carregar:** botão desactivado com indicador.
- **Erro:** email com formato inválido; ligação expirada; as passwords não coincidem.
- **Cheio:** «Se o email existir, enviámos uma ligação», sem dizer se a conta existe. A ligação vale 60 min (D10).

### 3. Lista de artigos com filtro
- **Propósito:** encontrar o artigo certo em segundos, à frente do cliente (FL2). **A imagem é a
  protagonista.**
- **Conteúdo:** pesquisa por nome/referência; filtros — ⚠ todos por confirmar (PRD §11: categoria? tipo, cor,
  preço?); grelha de cartões (imagem, nome, referência); indicador de rede/offline e data da última
  sincronização. Também (D11): «desde X €» no cartão quando os preços estão visíveis; interruptor de
  preços global (lista e artigo); acção «Juntar à selecção» no cartão, para montar a selecção do FL3.
- **Vazio:** catálogo sem artigos, ou nenhum resultado para o filtro (dois casos: «limpar filtros»
  só no segundo).
- **A carregar:** esqueletos dos cartões na mesma grelha.
- **Erro:** a lista não carregou (sem rede e sem catálogo descarregado) → acção «Tentar outra vez»
  e ligação para o ecrã 6.
- **Cheio:** 20+ artigos, imagens de proporções diferentes, nomes compridos.

### 4. Página do artigo
- **Propósito:** mostrar o artigo ao cliente: imagens, campos do tipo de produto, variantes (cor,
  tamanho) e escalões de preço **por variante**.
- **Conteúdo:** galeria de imagens grande; nome e referência; selector de variante (as amostras de
  cor mudam a imagem quando há imagem da variante); tabela de escalões (quantidade mínima → preço
  unitário) da variante escolhida; campos dinâmicos (lista de rótulo/valor, que pode ser longa);
  **interruptor «Mostrar/esconder preços» óbvio e rápido** (o estado vê-se de longe; com os preços
  escondidos, nenhum preço aparece no ecrã); acção «Partilhar» (vai para o ecrã 5); acção «Juntar à selecção» (D11).
- **Offline (D4):** «Partilhar» e «Juntar à selecção» desactivados, com o motivo («Partilhar precisa de rede»).
- **Vazio:** artigo sem imagens (substituto neutro); variante sem escalões → «Preço sob consulta» (D10).
- **A carregar:** esqueleto da galeria e da tabela.
- **Erro:** artigo não encontrado ou retirado do catálogo.
- **Cheio:** 6 imagens, 8 variantes, 5 escalões, 12 campos dinâmicos.

### 5. Criar partilha
- **Propósito:** enviar ao cliente exactamente o que ele pode ver (FL3).
- **Conteúdo:** os artigos escolhidos (um ou uma selecção, que se pode retirar); modo, como três
  opções claras: **com preços · sem preços · só imagens**; validade 7 · 30 · 90 dias, omissão 7 (D10); modo por omissão «Sem preços» (D10); gerar ligação → copiar e
  enviar (WhatsApp, email, partilha nativa).
- **Vazio:** nenhum artigo escolhido → como juntar artigos à selecção.
- **A carregar:** a gerar a ligação.
- **Erro:** sem rede (partilhar exige rede, D4); falhou a geração.
- **Cheio:** ligação gerada, com modo e validade visíveis e o botão «Copiar» confirmado.
- ⚠ TODO: quem revoga uma partilha? O vendedor não tem backoffice (PRD §4).

### 6. Offline / descarregar catálogo
- **Propósito:** pôr o catálogo no dispositivo para usar sem rede (FL4, D4 só leitura).
- **Conteúdo:** estado actual (descarregado / por descarregar / desactualizado); **data da última
  sincronização**; tamanho aproximado (as imagens contam); acção «Descarregar/Actualizar»; aviso
  de que, sem rede, só se consulta (não se partilha).
- **Vazio:** catálogo nunca descarregado.
- **A carregar:** progresso da descarga (artigos e imagens).
- **Erro:** descarga interrompida; sem espaço no dispositivo.
- **Cheio:** descarregado, com data e tamanho; versão em modo offline (banner «Sem rede — a ver a
  cópia de <data>»).

## Público (sem login)

### 7. Página da partilha
- **Propósito:** o cliente final revê no telemóvel o que lhe mostraram (P3). Usa a mesma linguagem
  visual, **sem a navegação da app**.
- **Conteúdo:** consoante o modo — **com preços** (artigos + variantes + escalões), **sem preços**
  (artigos + variantes, sem nenhum preço), **só imagens** (galeria com o nome do artigo, D11); até quando a ligação é válida; quem partilhou
  (D10).
- **Regra D5 (para quem converter):** em «sem preços» e «só imagens», nenhum preço entra no HTML nem
  no JSON enviado ao browser — esconder no ecrã não chega.
- TODO: com preços, mostra os preços actuais ou os do momento da partilha? (risco do FL3)
- **Vazio:** n/a (uma partilha tem sempre artigos); se os artigos saíram do catálogo → mensagem.
- **A carregar:** esqueleto.
- **Erro:** ligação expirada · revogada · inexistente (mensagens diferentes, nenhuma mostra dados).
- **Cheio:** os três modos, cada um com uma selecção de 5 artigos.

## Backoffice (Filament — só tema, não se desenha)

8. Tipos de produto e os seus campos
9. Artigos (campos, imagens, variantes, escalões)
10. Utilizadores (admin/vendedor)
11. Partilhas (listar, revogar)
