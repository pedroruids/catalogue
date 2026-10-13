/**
 * Terminar sessão apaga a cópia local do catálogo (preços e artigos não ficam num telemóvel partilhado).
 * Intercepta o envio dos formulários de logout (app do vendedor e Filament), apaga e só depois submete —
 * apagar «em paralelo» com o redirect podia ser cortado a meio pela navegação. Se o apagamento demorar,
 * o logout segue na mesma ao fim de 2 s.
 */
import { apagarCopia } from './catalogo';

const TEMPO_MAXIMO_MS = 2000;

document.addEventListener('submit', (evento) => {
    const form = evento.target;
    if (!(form instanceof HTMLFormElement) || evento.defaultPrevented) return;
    if (!new URL(form.action, location.href).pathname.endsWith('/logout')) return;

    evento.preventDefault();
    Promise.race([apagarCopia(), new Promise((resolve) => setTimeout(resolve, TEMPO_MAXIMO_MS))])
        .catch(() => {})
        .finally(() => form.submit());
});
