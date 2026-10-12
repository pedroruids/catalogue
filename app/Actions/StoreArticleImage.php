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
use Throwable;

/**
 * Guarda o original (disco privado) e gera os derivados WebP q80 (disco público):
 * thumb 400 px e screen 1200 px de largura, sem ampliar, com a orientação EXIF corrigida.
 *
 * Um lote é tudo-ou-nada: se uma imagem falhar, não fica nenhuma linha nem ficheiro do lote.
 */
class StoreArticleImage
{
    public const array SIZES = ['thumb' => 400, 'screen' => 1200];

    public const int QUALITY = 80;

    /** Descomprimida ocupa 4 bytes/px (16 MP = 64 MB): acima disto estoura o memory_limit de 128 MB. */
    public const int MAX_PIXELS = 16_000_000;

    /** Devolve a mensagem (PT-PT) se a imagem não puder ser processada com segurança, ou null. */
    public static function validationError(UploadedFile $file): ?string
    {
        $path = (string) $file->getRealPath();
        $info = @getimagesize($path);

        if ($info === false) {
            return 'O ficheiro não é uma imagem legível ou está corrompido.';
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return 'A imagem tem demasiados píxeis (máximo '.(self::MAX_PIXELS / 1_000_000).' megapíxeis).';
        }

        if (@imagecreatefromstring((string) file_get_contents($path)) === false) {
            return 'O ficheiro não é uma imagem legível ou está corrompido.';
        }

        return null;
    }

    public function handle(Article $article, UploadedFile $file): ArticleImage
    {
        return $this->handleMany($article, [$file]);
    }

    /**
     * @param  non-empty-array<int, UploadedFile>  $files
     * @return ArticleImage a última criada
     */
    public function handleMany(Article $article, array $files): ArticleImage
    {
        $written = [];

        try {
            return DB::transaction(function () use ($article, $files, &$written): ArticleImage {
                $article->newQuery()->whereKey($article->id)->lockForUpdate()->first();
                $images = $article->images();

                foreach ($files as $file) {
                    $paths = $this->writeFiles($article, $file, $written);

                    $image = $images->create([
                        'original_path' => $paths['original'],
                        'thumb_path' => $paths['thumb'],
                        'screen_path' => $paths['screen'],
                        'position' => (int) $images->max('position') + 1,
                        'is_primary' => ! $images->exists(),
                    ]);
                }

                return $image;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($written['local'] ?? []);
            Storage::disk('public')->delete($written['public'] ?? []);

            throw $e;
        }
    }

    /**
     * @param  array{local?: list<string>, public?: list<string>}  $written  acumula o que foi escrito, para limpar se algo falhar
     * @return array{original: string, thumb: string, screen: string}
     */
    private function writeFiles(Article $article, UploadedFile $file, array &$written): array
    {
        $path = (string) $file->getRealPath();
        $contents = (string) file_get_contents($path);
        $source = @imagecreatefromstring($contents);

        if (! $source instanceof GdImage) {
            throw new InvalidArgumentException('Ficheiro de imagem inválido.');
        }

        $source = $this->orient($source, $path);

        $name = (string) Str::ulid();
        $dir = "articles/{$article->id}";
        $original = "{$dir}/{$name}.".($file->guessExtension() ?? 'bin');
        $paths = ['original' => $original];

        foreach (self::SIZES as $size => $width) {
            $paths[$size] = "{$dir}/{$name}-{$size}.webp";
            Storage::disk('public')->put($paths[$size], $this->toWebp($source, $width));
            $written['public'][] = $paths[$size];
        }
        Storage::disk('local')->put($original, $contents);
        $written['local'][] = $original;

        return $paths;
    }

    private function orient(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);

        // Rodar e/ou espelhar para endireitar (imagerotate é anti-horário). 5 = transposta, 7 = transversa.
        [$angle, $flip] = match ($exif['Orientation'] ?? 1) {
            2 => [0, IMG_FLIP_HORIZONTAL],
            3 => [180, null],
            4 => [0, IMG_FLIP_VERTICAL],
            5 => [-90, IMG_FLIP_HORIZONTAL],
            6 => [-90, null],
            7 => [90, IMG_FLIP_HORIZONTAL],
            8 => [90, null],
            default => [0, null],
        };

        if ($angle !== 0) {
            $image = imagerotate($image, $angle, 0) ?: $image;
        }
        if ($flip !== null) {
            imageflip($image, $flip);
        }

        return $image;
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
