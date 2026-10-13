{{--
    Cópia local do catálogo (Onda 5a, Xavier #10): descarrega o catálogo e as imagens reduzidas para o
    telemóvel e mostra tamanho e data da última sincronização. Só escreve a cópia; lê-la é a Onda 5b.
    wire:ignore: o estado vive no Alpine/IndexedDB, não no servidor.
--}}
<section wire:ignore x-data="catalogoOffline" x-cloak x-show="suportado" aria-labelledby="catalogo-offline-titulo"
         class="flex flex-col gap-2 rounded-lg border border-border bg-surface px-4 py-3">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 id="catalogo-offline-titulo" class="text-[15px] font-bold">Catálogo no telemóvel</h2>
            <p class="text-sm text-muted" x-text="resumo">Ainda não descarregou o catálogo para este telemóvel.</p>
        </div>
        <button type="button" x-on:click="descarregar" x-bind:disabled="a_descarregar" x-bind:aria-busy="a_descarregar.toString()"
                class="flex h-11 shrink-0 items-center justify-center rounded-lg border-[1.5px] border-brand bg-surface px-4 text-[15px] font-bold text-brand hover:bg-surface-warm disabled:border-border-input disabled:bg-disabled disabled:text-disabled-text">
            <span x-text="a_descarregar ? 'A descarregar…' : (estado ? 'Actualizar' : 'Descarregar')">Descarregar</span>
        </button>
    </div>
    <p class="text-sm text-muted" role="status" aria-live="polite" x-show="progresso" x-text="progresso"></p>
    <p class="text-sm" role="status" aria-live="polite" x-show="mensagem" x-text="mensagem" x-bind:class="erro ? 'text-error' : 'text-success'"></p>
</section>
