<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_type_id
 * @property string $name
 * @property string $reference
 * @property string|null $description
 * @property ArticleStatus $status
 * @property array<string, mixed>|null $field_values
 * @property list<array{name: string, values: list<string>}>|null $variant_axes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductType $productType
 * @property-read Collection<int, ArticleImage> $images
 * @property-read ArticleImage|null $primaryImage
 * @property-read Collection<int, ArticleVariant> $variants
 */
#[Fillable(['product_type_id', 'name', 'reference', 'description', 'status', 'field_values', 'variant_axes'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // A FK em cascata apaga as linhas mas não os ficheiros: apagar pelo modelo.
        static::deleting(fn (Article $article) => $article->images->each->delete());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'field_values' => 'array',
            'variant_axes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ProductType, $this>
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * @return HasMany<ArticleImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ArticleImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasOne<ArticleImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ArticleImage::class)->where('is_primary', true);
    }

    /**
     * @return HasMany<ArticleVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ArticleVariant::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Preço «desde»: menor preço unitário (cêntimos) entre as variantes activas, ou null (sob consulta).
     * Carregar `variants.priceTiers` antes evita N+1.
     */
    public function fromPriceCents(): ?int
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->with('priceTiers')->get();

        return $variants->where('is_active', true)->map(fn (ArticleVariant $v): ?int => $v->fromPriceCents())->filter(fn (?int $c): bool => $c !== null)->min();
    }

    /**
     * Razão pela qual estes eixos não podem substituir os actuais, ou null se podem.
     * Retirar um eixo/valor em uso por variantes é recusado, e acrescentar um eixo deixaria
     * as variantes existentes sem valor nele.
     *
     * @param  array<int, array{name?: string|null, values?: array<int, string>|null}>  $axes
     */
    public function variantAxesProblem(array $axes): ?string
    {
        $variants = $this->variants()->get();

        if ($variants->isEmpty()) {
            return null;
        }

        $values = [];
        foreach ($axes as $axis) {
            $values[(string) ($axis['name'] ?? '')] = $axis['values'] ?? [];
        }

        $uses = [];
        foreach ($variants as $variant) {
            foreach ($variant->options as $axis => $value) {
                $key = isset($values[$axis]) ? ($value !== '' && in_array($value, $values[$axis], true) ? null : "O valor «{$value}» do eixo «{$axis}»") : "O eixo «{$axis}»";
                if ($key !== null) {
                    $uses[$key] = ($uses[$key] ?? 0) + 1;
                }
            }
        }

        $messages = [];
        foreach ($uses as $what => $count) {
            $messages[] = "{$what} está em uso por {$count} ".($count === 1 ? 'variante' : 'variantes').'.';
        }

        $known = array_keys($variants->first()->options);
        $added = array_diff(array_keys($values), $known);
        if ($messages === [] && $added !== []) {
            $messages[] = 'Não se acrescentam eixos enquanto houver variantes ('.$variants->count().'): apague-as ou crie o eixo antes de as criar.';
        }

        return $messages === [] ? null : implode(' ', $messages).' Retire-o das variantes primeiro.';
    }
}
