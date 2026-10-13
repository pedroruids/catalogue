<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\ArticleVariant;
use App\Models\FieldDefinition;
use App\Models\PriceTier;
use App\Models\ProductType;
use Illuminate\Support\Facades\Storage;

/**
 * Cópia do catálogo para o dispositivo do vendedor (D4, Xavier #10): só artigos publicados,
 * variantes activas, escalões e URLs das imagens REDUZIDAS. Lista branca explícita campo a campo:
 * nunca `toArray()` de um modelo, para o original (`original_path`) e o que vier a ser adicionado
 * às tabelas não saírem sem ninguém decidir. Os preços vão sempre na cópia; mostrá-los ou não
 * (interruptor D11) é decisão do ecrã que a ler (Xavier #13).
 */
final class CatalogSnapshot
{
    /** Mudar quando a forma do JSON mudar de maneira que o cliente tenha de saber. */
    public const SCHEMA = 1;

    /**
     * @return array{schema: int, version: string, generated_at: string, product_types: array<int, array<string, mixed>>, articles: array<int, array<string, mixed>>}
     */
    public static function build(): array
    {
        $types = ProductType::query()
            ->whereHas('articles', fn ($q) => $q->where('status', ArticleStatus::Published))
            ->with('fieldDefinitions')
            ->orderBy('id')
            ->get()
            ->map(fn (ProductType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'fields' => $type->fieldDefinitions->map(fn (FieldDefinition $field): array => [
                    'key' => $field->key,
                    'name' => $field->name,
                    'data_type' => $field->data_type->value,
                    'options' => $field->options,
                ])->all(),
            ])->values()->all();

        $articles = Article::query()
            ->where('status', ArticleStatus::Published)
            ->with([
                'images',
                'variants' => fn ($q) => $q->where('is_active', true)->with('priceTiers'),
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (Article $article): array => [
                'id' => $article->id,
                'product_type_id' => $article->product_type_id,
                'name' => $article->name,
                'reference' => $article->reference,
                'description' => $article->description,
                'field_values' => $article->field_values ?? [],
                'variant_axes' => $article->variant_axes ?? [],
                'updated_at' => $article->updated_at?->toIso8601String(),
                'images' => $article->images->map(fn (ArticleImage $image): array => [
                    'id' => $image->id,
                    'is_primary' => $image->is_primary,
                    'thumb' => self::path($image->thumb_path),
                    'screen' => self::path($image->screen_path),
                ])->all(),
                'variants' => $article->variants->map(fn (ArticleVariant $variant): array => [
                    'id' => $variant->id,
                    'reference' => $variant->reference,
                    'options' => $variant->options,
                    'image_id' => $variant->article_image_id,
                    'price_tiers' => $variant->priceTiers->map(fn (PriceTier $tier): array => [
                        'min_quantity' => $tier->min_quantity,
                        'unit_price_cents' => $tier->unit_price_cents,
                    ])->all(),
                ])->all(),
            ])->values()->all();

        $content = ['schema' => self::SCHEMA, 'product_types' => $types, 'articles' => $articles];

        return [
            'schema' => self::SCHEMA,
            // Hash do conteúdo (sem a data de geração): só muda quando o catálogo muda.
            'version' => substr(sha1((string) json_encode($content)), 0, 16),
            'generated_at' => now()->toIso8601String(),
            'product_types' => $types,
            'articles' => $articles,
        ];
    }

    /** Caminho (da raiz) servido pelo disco público: o cliente guarda-o e pede-o à mesma origem. */
    private static function path(string $stored): string
    {
        return (string) parse_url(Storage::disk('public')->url($stored), PHP_URL_PATH);
    }
}
