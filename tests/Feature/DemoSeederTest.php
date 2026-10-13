<?php

use App\Enums\ArticleStatus;
use App\Enums\Role;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleVariant;
use App\Models\PriceTier;
use App\Models\ProductType;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function counts(): array
{
    return [
        'users' => User::count(), 'types' => ProductType::count(), 'articles' => Article::count(),
        'images' => ArticleImage::count(), 'variants' => ArticleVariant::count(), 'tiers' => PriceTier::count(),
    ];
}

it('cria os dados de demonstração e é idempotente', function (): void {
    Storage::fake('local');
    Storage::fake('public');

    $this->seed(DemoSeeder::class);
    $first = counts();
    $this->seed(DemoSeeder::class);

    expect(counts())->toBe($first)
        ->and($first['types'])->toBe(3)
        ->and($first['articles'])->toBe(12)
        ->and($first['users'])->toBe(2);
    expect(Storage::disk('public')->allFiles())->toHaveCount($first['images'] * 2);
});

it('cria vendedor e admin de demonstração com o papel certo e sem tocar em utilizadores existentes', function (): void {
    Storage::fake('local');
    Storage::fake('public');
    User::factory()->create(['email' => 'vendedor@maravilha.test', 'name' => 'Já existia', 'role' => Role::Admin]);

    $this->seed(DemoSeeder::class);

    expect(User::where('email', 'vendedor@maravilha.test')->sole())
        ->name->toBe('Já existia')->role->toBe(Role::Admin);
    expect(User::where('email', 'admin@maravilha.test')->sole()->role)->toBe(Role::Admin);
});

it('o vendedor de demonstração tem o papel vendedor e entra com password', function (): void {
    Storage::fake('local');
    Storage::fake('public');

    $this->seed(DemoSeeder::class);

    $vendedor = User::where('email', 'vendedor@maravilha.test')->sole();
    expect($vendedor->role)->toBe(Role::Vendedor)
        ->and(Hash::check('password', $vendedor->password))->toBeTrue();
});

it('os artigos têm imagens, variantes e escalões coerentes', function (): void {
    Storage::fake('local');
    Storage::fake('public');

    $this->seed(DemoSeeder::class);

    $articles = Article::with(['images', 'variants.priceTiers', 'productType.fieldDefinitions'])->get();

    foreach ($articles as $article) {
        expect($article->images)->not->toBeEmpty()
            ->and($article->images->where('is_primary', true))->toHaveCount(1)
            ->and($article->field_values)->not->toBeEmpty();
        Storage::disk('public')->assertExists([$article->images[0]->thumb_path, $article->images[0]->screen_path]);
        Storage::disk('local')->assertExists($article->images[0]->original_path);
        // Valores só nas chaves definidas no tipo (D12).
        expect(array_diff(array_keys($article->field_values), $article->productType->fieldDefinitions->pluck('key')->all()))->toBeEmpty();
    }

    $withVariants = $articles->filter(fn (Article $a) => $a->variants->isNotEmpty());
    expect($withVariants->count())->toBeGreaterThanOrEqual(8)
        ->and($articles->where('status', ArticleStatus::Draft))->toHaveCount(1);

    foreach ($withVariants as $article) {
        foreach ($article->variants as $variant) {
            expect($variant->priceTiers->count())->toBeGreaterThan(1)
                ->and($variant->priceTiers->pluck('unit_price_cents')->all())->toBe($variant->priceTiers->sortByDesc('min_quantity')->reverse()->pluck('unit_price_cents')->sortDesc()->values()->all());
        }
    }

    // Há artigos sem variantes (preço sob consulta).
    expect($articles->filter(fn (Article $a) => $a->variants->isEmpty())->count())->toBeGreaterThan(0);
});
