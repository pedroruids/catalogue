<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\ArticleImage;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Guarda o original (disco privado) e gera os derivados WebP q80 (disco público):
 * thumb 400 px e screen 1200 px de largura, sem ampliar, com a orientação EXIF corrigida.
 */
class StoreArticleImage
{
    public const array SIZES = ['thumb' => 400, 'screen' => 1200];

    public const int QUALITY = 80;

    public function handle(Article $article, UploadedFile $file): ArticleImage
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($contents);

        if (! $source instanceof GdImage) {
            throw new InvalidArgumentException('Ficheiro de imagem inválido.');
        }

        $source = $this->orient($source, $contents);

        $name = (string) Str::ulid();
        $dir = "articles/{$article->id}";
        $original = "{$dir}/{$name}.".($file->guessExtension() ?? 'bin');
        $paths = [];

        foreach (self::SIZES as $size => $width) {
            $paths[$size] = "{$dir}/{$name}-{$size}.webp";
            Storage::disk('public')->put($paths[$size], $this->toWebp($source, $width));
        }
        Storage::disk('local')->put($original, $contents);

        return DB::transaction(function () use ($article, $original, $paths): ArticleImage {
            $article->newQuery()->whereKey($article->id)->lockForUpdate()->first();
            $images = $article->images();

            return $images->create([
                'original_path' => $original,
                'thumb_path' => $paths['thumb'],
                'screen_path' => $paths['screen'],
                'position' => (int) $images->max('position') + 1,
                'is_primary' => ! $images->exists(),
            ]);
        });
    }

    private function orient(GdImage $image, string $contents): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    private function toWebp(GdImage $source, int $maxWidth): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $target = min($width, $maxWidth);
        $resized = imagescale($source, $target, max(1, (int) round($height * $target / $width)));

        if ($resized === false) {
            throw new InvalidArgumentException('Não foi possível redimensionar a imagem.');
        }

        imagepalettetotruecolor($resized);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        ob_start();
        imagewebp($resized, null, self::QUALITY);

        return (string) ob_get_clean();
    }
}
