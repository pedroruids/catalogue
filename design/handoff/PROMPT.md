# Prompt para o Claude Design

Faz upload dos 4 ficheiros desta pasta (`PRD.md`, `BRAND.md`, `DESIGN.md`, `ECRAS.md`) e cola o
texto abaixo.

---

Vou dar-te 4 documentos de um produto (nome interno Catalogue; **o nome visível na interface é
«Maravilha»**): o catálogo digital de uma empresa de
brindes, que o vendedor usa no telemóvel à frente do cliente e que partilha com ele por ligação.
Cria:

1. **O design system**: tokens em CSS custom properties e componentes base (botões, campos,
   cartão de artigo, interruptor de preços, selector de variante/amostras de cor, tabela de
   escalões, banner offline, mensagens de estado). Segue o DESIGN.md à letra, porque é o contrato
   e não uma inspiração: Fraunces nos títulos, Manrope no corpo, paleta marinho clássico
   (`#1E3A5F` · `#C9A227` · `#FAFAF7` · `#14202E`), cantos de 0.5rem, densidade equilibrada.
   A direcção é «quente e humano»: trabalha a temperatura nos neutros e nas superfícies (derivados
   quentes do fundo) e dá destaque às imagens, **sem trocar as quatro cores base**. Respeita a
   tabela de contraste do BRAND.md: o dourado `#C9A227` nunca é texto sobre o fundo claro.
   Entrega uma página `design-system.html` com todos os tokens e componentes juntos.
2. **Os ecrãs 1 a 7 do ECRAS.md**, um ficheiro HTML autónomo por ecrã, cada um com **OS QUATRO
   ESTADOS (vazio · a carregar · erro · cheio)** separados por cabeçalho. Desenha primeiro a 360 px
   e garante que funciona a 768 px e a 1280 px, sem scroll horizontal a 360 px. Os ecrãs 8 a 11
   (backoffice) não se desenham.
3. **Dados de exemplo plausíveis de brindes**, nunca lorem ipsum: canetas, garrafas térmicas,
   sacos de algodão, t-shirts, cadernos, com referências, variantes de cor e tamanho e escalões
   (ex.: 50 · 100 · 250 · 500 unidades). Preços em euros no formato português (1,25 €).
4. **Comentários HTML** a marcar que componente do design system cada bloco usa.

Regras:
- Formato: HTML/CSS/JS autónomo, sem frameworks externas (Google Fonts é permitido para Fraunces e
  Manrope); tokens só por custom properties, sem valores soltos nos ecrãs.
- Todos os ecrãs usam **os mesmos tokens**, definidos uma vez e não copiados com variações.
- Texto da interface em **português de Portugal** (telemóvel, ecrã, partilhar, ligação), nunca
  português do Brasil.
- Não há logótipo: usa um espaço reservado neutro com «Maravilha» em texto e não desenhes logótipo.
- O interruptor «Mostrar/esconder preços» tem de se perceber de longe. Com os preços escondidos,
  não aparece nenhum preço no ecrã.
- Acessibilidade WCAG 2.1 AA: contraste, foco visível, rótulos nos campos; alvos de toque ≥ 44 px (meta do projecto, acima do AA).

---

**Quando terminares:** exporta os ficheiros e põe-nos em `design/claude-design/` no repo. Depois
avisa o JOCA, que os valida ecrã a ecrã e converte para Livewire + Flux.
