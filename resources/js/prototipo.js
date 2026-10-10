/*
 * Estado partilhado do protótipo estático: Alpine.store('proto').
 * O Alpine vem do Livewire (@livewireScripts no layout) e arranca no DOMContentLoaded;
 * este módulo corre antes disso, por isso o listener de 'alpine:init' chega a tempo.
 *
 * Persistido em localStorage (chave 'maravilha.proto'), sempre com try/catch:
 * sem storage (janela privada, bloqueado) o protótipo funciona na mesma, só não lembra.
 *
 * ⚠ Preços escondidos aqui são só x-show, porque é um protótipo estático. Na implementação
 * real a remoção é feita no servidor (D5). Na página pública (/p/sem-precos, /p/so-imagens)
 * já é o servidor que remove: esse HTML não contém preços.
 */
const CHAVE = 'maravilha.proto';

function ler() {
    try {
        return JSON.parse(localStorage.getItem(CHAVE)) ?? {};
    } catch {
        return {};
    }
}

function gravar(estado) {
    try {
        localStorage.setItem(CHAVE, JSON.stringify(estado));
    } catch {
        // sem storage: ignora
    }
}

document.addEventListener('alpine:init', () => {
    const guardado = ler();
    const rede = new URLSearchParams(window.location.search).get('rede');

    window.Alpine.store('proto', {
        precosVisiveis: guardado.precosVisiveis ?? true,
        seleccao: Array.isArray(guardado.seleccao) ? guardado.seleccao : [],
        offline: rede === 'off' ? true : rede === 'on' ? false : (guardado.offline ?? false),
        seletorAberto: guardado.seletorAberto ?? true,

        get online() {
            return !this.offline;
        },
        get total() {
            return this.seleccao.length;
        },

        togglePrecos() {
            this.precosVisiveis = !this.precosVisiveis;
            this.guardar();
        },
        toggleRede() {
            this.offline = !this.offline;
            this.guardar();
        },
        toggleSeletor() {
            this.seletorAberto = !this.seletorAberto;
            this.guardar();
        },
        naSeleccao(ref) {
            return this.seleccao.includes(ref);
        },
        juntar(ref) {
            if (this.offline || this.naSeleccao(ref)) return;
            this.seleccao.push(ref);
            this.guardar();
        },
        retirar(ref) {
            this.seleccao = this.seleccao.filter((r) => r !== ref);
            this.guardar();
        },
        toggleSeleccao(ref) {
            if (this.offline) return;
            this.naSeleccao(ref) ? this.retirar(ref) : this.juntar(ref);
        },
        limparSeleccao() {
            this.seleccao = [];
            this.guardar();
        },

        guardar() {
            gravar({
                precosVisiveis: this.precosVisiveis,
                seleccao: this.seleccao,
                offline: this.offline,
                seletorAberto: this.seletorAberto,
            });
        },
    });
});
