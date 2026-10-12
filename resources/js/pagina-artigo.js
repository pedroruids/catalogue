/**
 * Página do artigo (Alpine): escolha de variante pelos eixos e imagem mostrada.
 * Os dados vêm do servidor sem preços; os escalões estão no HTML e só se mostram/escondem aqui.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('paginaArtigo', (inicial, variantes, imagens) => ({
        sel: { ...inicial.sel },
        img: inicial.img,
        variantes,
        imagens,

        // Índice da variante activa que corresponde à escolha (-1: combinação indisponível).
        get indice() {
            return this.variantes.findIndex((v) => Object.entries(this.sel).every(([k, val]) => v.options[k] === val));
        },

        // Escolher uma opção; se a variante resultante tem imagem, passa a mostrá-la.
        escolher(eixo, valor) {
            this.sel[eixo] = valor;
            const v = this.variantes[this.indice];
            if (v && v.image !== null) this.img = v.image;
        },
    }));
});
