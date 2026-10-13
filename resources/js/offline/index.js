import './sair';
import {
    arrancarActualizacaoAutomatica,
    formatarData,
    formatarTamanho,
    lerEstado,
    pedirPersistencia,
    sincronizar,
} from './catalogo';

// Só na app do vendedor (o layout põe `data-app` no body): nunca no /admin nem na página pública.
if (document.body?.hasAttribute('data-app')) {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
        });
    }

    if ('indexedDB' in window && 'caches' in window) arrancarActualizacaoAutomatica();
}

/** Bloco «Catálogo offline» (resources/views/components/app/catalogo-offline.blade.php). */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('catalogoOffline', () => ({
        suportado: 'indexedDB' in window && 'caches' in window,
        estado: null,
        a_descarregar: false,
        progresso: '',
        mensagem: '',
        erro: false,

        async init() {
            if (!this.suportado) return;
            await this.ler();
            window.addEventListener('catalogo:actualizado', () => this.ler());
        },

        async ler() {
            try {
                this.estado = await lerEstado();
            } catch {
                this.estado = null;
            }
        },

        get resumo() {
            if (!this.estado) return 'Ainda não descarregou o catálogo para este telemóvel.';
            const falta = this.estado.imagens_em_falta ? ` · ${this.estado.imagens_em_falta} sem imagem` : '';
            return `${this.estado.artigos} artigos · ${formatarTamanho(this.estado.bytes)} · actualizado a ${formatarData(this.estado.sincronizada_em)}${falta}`;
        },

        async descarregar() {
            if (this.a_descarregar) return;
            this.a_descarregar = true;
            this.erro = false;
            this.mensagem = '';
            this.progresso = 'A preparar…';
            await pedirPersistencia();

            try {
                const { resultado, erro } = await sincronizar({
                    aoProgresso: (feitas, total) => (this.progresso = `A descarregar imagens ${feitas}/${total}…`),
                });
                this.mensagem = {
                    actualizada: 'Catálogo descarregado.',
                    igual: 'Já tem a versão mais recente.',
                    'sem-espaco':
                        'Sem espaço no telemóvel para guardar o catálogo. Liberte espaço e tente outra vez; a cópia anterior ficou como estava.',
                    'sem-rede': 'Sem ligação. Tente outra vez quando tiver rede; a cópia anterior ficou como estava.',
                    erro: `Não foi possível descarregar${erro ? ` (${erro})` : ''}. Tente outra vez.`,
                }[resultado];
                this.erro = ['erro', 'sem-rede', 'sem-espaco'].includes(resultado);
            } catch {
                this.mensagem = 'Não foi possível descarregar. Tente outra vez.';
                this.erro = true;
            } finally {
                this.a_descarregar = false;
                this.progresso = '';
                await this.ler();
            }
        },
    }));
});
