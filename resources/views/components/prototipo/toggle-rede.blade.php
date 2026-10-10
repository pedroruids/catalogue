{{-- Simula sem rede ($store.proto.toggleRede). Também se liga por ?rede=off / ?rede=on. Usado dentro do seletor-estado. --}}
<button type="button" x-data x-on:click="$store.proto.toggleRede()" :aria-pressed="$store.proto.offline.toString()"
        {{ $attributes->class('flex h-8 shrink-0 items-center gap-1.5 rounded-full border border-white/30 px-3 text-xs font-semibold') }}>
    <span x-show="! $store.proto.offline" class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-success-bg"></span> Com rede</span>
    <span x-show="$store.proto.offline" x-cloak class="flex items-center gap-1.5"><x-prototipo.icone nome="sem-wifi" class="size-4" /> Sem rede</span>
</button>
