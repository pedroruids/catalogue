<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Support\PriceVisibility;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// Página mínima (placeholder): o conteúdo do artigo é do Xavier #8.
new #[Layout('components.app.layout')] #[Title('Artigo')] class extends Component {
    public Article $article;

    public function togglePrices(): void
    {
        PriceVisibility::toggle();
    }

    public function mount(Article $article): void
    {
        abort_unless($article->status === ArticleStatus::Published, 404);

        $this->article = $article;
    }
}; ?>

<div>
    <x-app.app-bar voltar="Artigos" :show-prices="\App\Support\PriceVisibility::visible()" />

    <main class="mx-auto flex max-w-[1240px] flex-col gap-2 px-4 pt-5 pb-10">
        <h1 class="font-display text-[28px] font-semibold">{{ $article->name }}</h1>
        <span class="text-sm text-muted tabular-nums">{{ $article->reference }}</span>
    </main>
</div>
