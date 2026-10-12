<?php

namespace App\Models;

use Database\Factories\PriceTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

/**
 * @property int $id
 * @property int $article_variant_id
 * @property int $min_quantity
 * @property int $unit_price_cents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ArticleVariant $variant
 */
#[Fillable(['article_variant_id', 'min_quantity', 'unit_price_cents'])]
class PriceTier extends Model
{
    /** Tecto de negócio do preço unitário: 1 000 000,00 € (a coluna aguenta 4 294 967 295 cêntimos). */
    public const MAX_UNIT_PRICE_CENTS = 100_000_000;

    /** Tecto da quantidade mínima de um escalão. */
    public const MAX_MIN_QUANTITY = 1_000_000;

    /** @use HasFactory<PriceTierFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ArticleVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ArticleVariant::class, 'article_variant_id');
    }

    /** «1 234,50 €» */
    public static function formatCents(int $cents): string
    {
        return (string) Number::currency($cents / 100, 'EUR', 'pt_PT');
    }

    /** «1,25» → 125; null se não for um valor em euros válido (até 2 casas, sem float). */
    public static function centsFromInput(mixed $input): ?int
    {
        $clean = str_replace([' ', "\u{00A0}", '€'], '', (string) $input);

        if (! preg_match('/^(\d{1,12})(?:[.,](\d{1,2}))?$/', $clean, $m)) {
            return null;
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    /** 125 → «1,25» (para o campo do formulário, sem separador de milhares). */
    public static function inputFromCents(int $cents): string
    {
        return intdiv($cents, 100).','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
