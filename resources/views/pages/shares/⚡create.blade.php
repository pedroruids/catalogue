<?php

use App\Actions\CreateShare;
use App\Enums\ShareMode;
use App\Models\Article;
use App\Models\Share;
use App\Support\Selection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.app.layout')] #[Title('Partilhar com o cliente')] class extends Component {
    /** Valor do `ShareMode`; por omissão «Sem preços» (D10). */
    public string $mode = 'without_prices';

    public int $days = Share::DEFAULT_DAYS;

    /** O link gerado; o token só existe aqui, não se recupera. */
    #[Locked]
    public ?string $url = null;

    #[Locked]
    public string $generatedMode = '';

    #[Locked]
    public string $generatedUntil = '';

    #[Locked]
    public int $generatedCount = 0;

    public function remove(int $id): void
    {
        Selection::remove($id);
    }

    public function clearSelection(): void
    {
        Selection::clear();
    }

    public function generate(): void
    {
        // Duplo clique: o Livewire serializa os pedidos e o segundo já encontra o link feito.
        if ($this->url !== null) {
            return;
        }

        $this->validate(['mode' => ['required', Rule::enum(ShareMode::class)]], messages: ['mode.required' => 'Escolha o que o cliente vê.', 'mode.enum' => 'Escolha um dos três modos: com preços, sem preços ou só imagens.']);

        $ids = Selection::ids();
        $mode = ShareMode::from($this->mode);

        // Falhas (selecção vazia, validade forjada…) saem como ValidationException e o Livewire põe-nas por campo.
        $result = app(CreateShare::class)->handle(auth()->user(), $ids, $mode, $this->days);

        $this->url = $result['url'];
        $this->generatedMode = $mode->getLabel();
        $this->generatedUntil = $result['share']->expires_at->format('d/m/Y');
        $this->generatedCount = count($ids);
    }

    public function again(): void
    {
        $this->reset('url', 'generatedMode', 'generatedUntil', 'generatedCount');
    }

    /** @return Collection<int, Article> na ordem em que se juntaram */
    public function articles(): Collection
    {
        $ids = Selection::ids();

        return Article::query()->whereIn('id', $ids)->with('primaryImage')->get()
            ->sortBy(fn (Article $a): int => (int) array_search($a->id, $ids, true))
            ->values();
    }
}; ?>

@php
    $articles = $this->articles();
    $count = $articles->count();
    $modes = [
        ShareMode::WithPrices->value => 'Artigos, variantes e preços por quantidade.',
        ShareMode::WithoutPrices->value => 'Artigos e variantes. Nenhum preço é enviado.',
        ShareMode::ImagesOnly->value => 'Galeria com o nome de cada artigo.',
    ];
    $label = fn (int $n): string => $n === 1 ? '1 artigo' : $n.' artigos';
@endphp

<div>
    <x-app.app-bar voltar="Artigos" :sem-seleccao="true" />

    <main class="mx-auto flex max-w-[1040px] flex-col gap-5 px-4 pt-5 pb-12">
        <h1 class="font-display text-[28px] font-semibold">Partilhar com o cliente</h1>

        @if ($url !== null)
            {{-- Link gerado --}}
            <section x-data="{
                        url: @js($url),
                        copiada: false,
                        async copiar() {
                            try {
                                await navigator.clipboard.writeText(this.url);
                                this.copiada = true;
                            } catch (e) {
                                try {
                                    this.$refs.url.select();
                                    this.copiada = document.execCommand('copy');
                                } catch (e2) {
                                    this.copiada = false;
                                }
                            }
                        },
                        async enviar(canal) {
                            if (navigator.share) {
                                try { await navigator.share({ title: 'Artigos da Maravilha', url: this.url }); } catch (e) {}
                                return;
                            }
                            const texto = encodeURIComponent(this.url);
                            if (canal === 'whatsapp') window.open('https://wa.me/?text=' + texto, '_blank', 'noopener');
                            else if (canal === 'email') window.location.href = 'mailto:?subject=' + encodeURIComponent('Artigos da Maravilha') + '&body=' + texto;
                            else this.copiar();
                        },
                    }"
                    class="flex flex-col gap-[18px] rounded-lg border border-border bg-surface px-5 py-6" aria-labelledby="titulo-pronta">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-success-bg text-success">
                        <x-app.icone nome="visto" class="size-[22px]" />
                    </span>
                    <h2 id="titulo-pronta" class="font-display text-[22px] font-semibold">Ligação pronta</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="flex h-8 items-center rounded-full bg-brand px-3 text-sm font-bold text-bg">{{ $generatedMode }}</span>
                    <span class="flex h-8 items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums">Válida até {{ $generatedUntil }}</span>
                    <span class="flex h-8 items-center rounded-full bg-surface-warm px-3 text-sm font-semibold tabular-nums">{{ $label($generatedCount) }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <input x-ref="url" type="text" readonly value="{{ $url }}" aria-label="Ligação da partilha" x-on:focus="$event.target.select()"
                           class="h-12 min-w-0 flex-[1_1_220px] truncate rounded-lg border-[1.5px] border-border-input bg-bg px-3.5 text-[15px]">
                    <button type="button" x-on:click="copiar()"
                            x-bind:class="copiada ? 'bg-success text-surface' : 'bg-brand text-bg hover:bg-brand-hover'"
                            class="flex h-12 flex-none items-center justify-center gap-2 rounded-lg bg-brand px-5 text-base font-bold text-bg">
                        <span x-show="! copiada" class="flex items-center gap-2"><x-app.icone nome="copiar" class="size-[18px]" />Copiar link</span>
                        <span x-show="copiada" x-cloak class="flex items-center gap-2"><x-app.icone nome="visto" class="size-[18px]" />Copiada</span>
                    </button>
                    <span class="sr-only" aria-live="polite" x-text="copiada ? 'Ligação copiada' : ''"></span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-bold text-muted-2">Enviar por</span>
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(110px,1fr))] gap-2">
                        @foreach (['whatsapp' => 'WhatsApp', 'email' => 'Email', 'mais' => 'Partilhar…'] as $canal => $rotulo)
                            <button type="button" x-on:click="enviar('{{ $canal }}')"
                                    class="h-12 rounded-lg border-[1.5px] border-brand bg-surface text-[15px] font-bold text-brand">{{ $rotulo }}</button>
                        @endforeach
                    </div>
                </div>
                <button type="button" wire:click="again" class="h-11 self-start px-1 text-[15px] font-bold text-brand underline">Nova partilha</button>
            </section>
        @elseif ($count === 0)
            {{-- Vazio --}}
            <x-app.estado-vazio icone="saco" titulo="Ainda não escolheu artigos"
                texto="Na lista ou na página de um artigo, toque em «Juntar» para o trazer para aqui."
                class="rounded-lg border border-border bg-surface">
                <a href="{{ route('articles.index') }}"
                   class="flex h-12 items-center justify-center rounded-lg bg-brand px-5 text-base font-bold text-bg hover:bg-brand-hover">Ver artigos</a>
                @error('articles')<p role="alert" class="text-sm font-semibold text-error">{{ $message }}</p>@enderror
            </x-app.estado-vazio>
        @else
            <div class="flex flex-wrap items-start gap-x-10 gap-y-6">
                <section class="flex min-w-0 flex-[1_1_360px] flex-col gap-2.5" aria-labelledby="titulo-artigos">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="titulo-artigos" class="text-base font-bold">{{ $label($count) }}</h2>
                        <button type="button" wire:click="clearSelection" class="h-11 px-1 text-[15px] font-bold text-brand underline">Limpar selecção</button>
                    </div>
                    <ul class="flex flex-col rounded-lg border border-border bg-surface">
                        @foreach ($articles as $article)
                            <li wire:key="sel-{{ $article->id }}" class="flex items-center gap-3 border-b border-border-soft py-2.5 pr-1.5 pl-3 last:border-b-0">
                                @if ($article->primaryImage)
                                    <img src="{{ $article->primaryImage->thumb_url }}" alt="" loading="lazy" class="size-14 shrink-0 rounded-md bg-img-placeholder object-cover">
                                @else
                                    <div class="bg-riscas size-14 shrink-0 rounded-md" aria-hidden="true"></div>
                                @endif
                                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span class="truncate text-[15px] leading-snug font-bold">{{ $article->name }}</span>
                                    <span class="text-[13px] text-muted">{{ $article->reference }}</span>
                                </div>
                                <button type="button" wire:click="remove({{ $article->id }})" aria-label="Retirar {{ $article->name }}"
                                        class="flex size-11 shrink-0 items-center justify-center rounded-lg text-muted-2 hover:bg-surface-warm">
                                    <x-app.icone nome="fechar" class="size-5" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    @error('articles')<p role="alert" class="text-sm font-semibold text-error">{{ $message }}</p>@enderror
                </section>

                <section class="flex min-w-0 flex-[1_1_360px] flex-col gap-5">
                    <fieldset class="flex flex-col gap-2.5">
                        <legend class="mb-2.5 text-base font-bold">O que o cliente vê</legend>
                        @foreach ($modes as $value => $description)
                            <label wire:key="modo-{{ $value }}" class="group flex cursor-pointer items-start gap-3 rounded-lg border border-border-input bg-surface px-[17px] py-[15px] hover:border-brand has-checked:border-2 has-checked:border-brand has-checked:px-4 has-checked:py-3.5 has-focus-visible:foco-marca">
                                <input type="radio" name="modo" value="{{ $value }}" wire:model="mode" class="sr-only">
                                <span class="mt-px flex size-[22px] shrink-0 items-center justify-center rounded-full border-2 border-border-input group-has-checked:border-brand group-has-checked:bg-brand" aria-hidden="true">
                                    <span class="hidden size-2 rounded-full bg-bg group-has-checked:block"></span>
                                </span>
                                <span class="flex flex-col gap-0.5">
                                    <span class="text-base font-bold">{{ ShareMode::from($value)->getLabel() }}</span>
                                    <span class="text-sm leading-[1.4] text-muted-2">{{ $description }}</span>
                                </span>
                            </label>
                        @endforeach
                        @error('mode')<p role="alert" class="text-sm font-semibold text-error">{{ $message }}</p>@enderror
                    </fieldset>

                    <div class="flex flex-col gap-2.5">
                        <h2 id="titulo-validade" class="text-base font-bold">Validade</h2>
                        <div role="group" aria-labelledby="titulo-validade" class="grid grid-cols-3 overflow-hidden rounded-lg border border-border-input bg-surface">
                            @foreach (Share::VALIDITY_DAYS as $option)
                                <button type="button" wire:key="dias-{{ $option }}" wire:click="$set('days', {{ $option }})" aria-pressed="{{ $days === $option ? 'true' : 'false' }}"
                                        @class([
                                            'h-11 text-[15px]',
                                            'bg-brand font-bold text-bg' => $days === $option,
                                            'bg-surface font-semibold text-text' => $days !== $option,
                                        ])>{{ $option }} dias</button>
                            @endforeach
                        </div>
                        <span class="text-sm text-muted">Expira a <span class="tabular-nums">{{ now()->addDays($days)->format('d/m/Y') }}</span>.</span>
                        @error('days')<p role="alert" class="text-sm font-semibold text-error">{{ $message }}</p>@enderror
                    </div>

                    <button type="button" wire:click="generate" wire:loading.attr="disabled" wire:target="generate"
                            class="flex h-[52px] items-center justify-center gap-2.5 rounded-lg bg-brand px-5 text-[17px] font-bold text-bg hover:bg-brand-hover disabled:cursor-not-allowed disabled:bg-disabled disabled:text-disabled-text">
                        <span wire:loading wire:target="generate"><x-app.icone nome="spinner" class="size-[18px]" /></span>
                        <span wire:loading.remove wire:target="generate">Gerar ligação</span>
                        <span wire:loading wire:target="generate">A gerar ligação…</span>
                    </button>
                </section>
            </div>
        @endif
    </main>
</div>
