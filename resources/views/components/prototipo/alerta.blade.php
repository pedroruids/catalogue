{{--
    Alerta em linha. Props: tipo ('erro' | 'aviso' | 'sucesso') · titulo (string|null, a negrito antes do texto)
           · icone (nome do <x-prototipo.icone>; omissão: alerta / sem-wifi / visto)
    Slot: default (texto). Erro usa role="alert"; os outros role="status".
--}}
@props(['tipo' => 'erro', 'titulo' => null, 'icone' => null])

<div role="{{ $tipo === 'erro' ? 'alert' : 'status' }}" {{ $attributes->class([
    'flex items-start gap-2.5 rounded-lg border px-3.5 py-3 text-[15px] leading-normal',
    'border-error-border bg-error-bg font-semibold text-error' => $tipo === 'erro',
    'border-warn-border bg-warn-bg text-text' => $tipo === 'aviso',
    'border-success-bg bg-success-bg font-semibold text-success' => $tipo === 'sucesso',
]) }}>
    <x-prototipo.icone :nome="$icone ?? match ($tipo) { 'erro' => 'alerta', 'aviso' => 'sem-wifi', default => 'visto' }" class="mt-0.5 size-5" />
    <div>
        @if ($titulo)<strong class="font-bold">{{ $titulo }}</strong> @endif{{ $slot }}
    </div>
</div>
