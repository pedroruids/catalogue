{{--
    Ícone do protótipo. Nomes PT → Heroicons do Flux (traço forçado a 2 px dentro de [data-proto]);
    o que o Flux não tem vai em SVG inline (traço 2 px, estilo Lucide, tirado dos mockups).
    Tamanho por classe (omissão size-5 = 20 px). Decorativo: aria-hidden.
--}}
@props(['nome'])

@php
    $flux = [
        'olho' => 'eye', 'olho-cortado' => 'eye-slash', 'saco' => 'shopping-bag', 'lupa' => 'magnifying-glass',
        'mais' => 'plus', 'visto' => 'check', 'partilhar' => 'share', 'chevron-esquerda' => 'chevron-left',
        'chevron-baixo' => 'chevron-down', 'fechar' => 'x-mark', 'copiar' => 'clipboard-document',
        'descarregar' => 'arrow-down-tray', 'info' => 'information-circle', 'alerta' => 'exclamation-triangle',
        'envelope' => 'envelope', 'relogio' => 'clock', 'corrente' => 'link', 'caixa' => 'cube', 'foto' => 'photo',
    ][$nome] ?? null;
    $classe = $attributes->get('class') ? '' : 'size-5';
@endphp

@if ($flux)
    <flux:icon :name="$flux" {{ $attributes->class(['shrink-0', $classe]) }} />
@elseif ($nome === 'sem-wifi')
    <svg {{ $attributes->class(['shrink-0', $classe]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M5 12.5a10 10 0 0 1 4-2.3"/><path d="M15.5 10.4A10 10 0 0 1 19 12.5"/><path d="M12 20h.01"/></svg>
@elseif ($nome === 'spinner')
    <svg {{ $attributes->class(['shrink-0 animate-spin', $classe]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
@endif
