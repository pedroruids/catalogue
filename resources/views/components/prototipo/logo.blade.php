{{-- Logótipo provisório (ainda não há asset): «Maravilha» em caixa tracejada. Props: grande (bool, login = 30 px). --}}
@props(['grande' => false])
<div {{ $attributes->class([
    'inline-flex items-center rounded-lg border border-dashed border-placeholder-logo font-display font-semibold text-brand',
    'h-9 px-2 text-[19px]' => ! $grande,
    'h-14 px-3 text-[30px]' => $grande,
]) }}>Maravilha</div>
