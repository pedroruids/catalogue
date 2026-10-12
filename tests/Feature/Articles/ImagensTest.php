<?php

use App\Actions\StoreArticleImage;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Articles\RelationManagers\ImagesRelationManager;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $this->article = Article::factory()->create();
});

function gerir(Article $article)
{
    return Livewire::test(ImagesRelationManager::class, ['ownerRecord' => $article, 'pageClass' => EditArticle::class]);
}

function imagem(string $nome, int $w = 2000, int $h = 1000): UploadedFile
{
    return UploadedFile::fake()->image($nome, $w, $h);
}

function carregar(Article $article, array $files)
{
    return gerir($article)->callTableAction('create', data: ['files' => $files]);
}

/** Todas as mensagens de validação do componente, numa só string. */
function erros($componente): string
{
    return implode(' | ', $componente->errors()->all());
}

function largura(string $path): array
{
    $info = getimagesizefromstring(Storage::disk('public')->get($path));

    return [$info[0], $info[1], $info['mime']];
}

it('gera os derivados WebP 400 e 1200 e guarda o original no disco privado', function () {
    carregar($this->article, [imagem('a.jpg', 2000, 1000)])->assertHasNoFormErrors();

    $img = $this->article->images()->sole();

    expect(largura($img->thumb_path))->toBe([400, 200, 'image/webp'])
        ->and(largura($img->screen_path))->toBe([1200, 600, 'image/webp']);
    Storage::disk('local')->assertExists($img->original_path);
    Storage::disk('public')->assertMissing($img->original_path);
    expect($img->toArray())->not->toHaveKey('original_path')
        ->and($img->thumb_url)->toContain($img->thumb_path)
        ->and($img->screen_url)->toContain($img->screen_path);
});

it('não amplia imagens mais pequenas que o derivado', function () {
    carregar($this->article, [imagem('p.png', 300, 150)]);

    $img = $this->article->images()->sole();

    expect(largura($img->thumb_path)[0])->toBe(300)
        ->and(largura($img->screen_path)[0])->toBe(300);
});

it('corrige a orientação EXIF antes de redimensionar', function () {
    $im = imagecreatetruecolor(1000, 500);
    $tmp = tempnam(sys_get_temp_dir(), 'exif');
    imagejpeg($im, $tmp);
    // Insere um segmento EXIF APP1 com Orientation=6 (rodar 90 graus).
    $exif = "Exif\0\0".'MM'."\x00\x2a\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00"."\x00\x00\x00\x00";
    $jpeg = file_get_contents($tmp);
    $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
    file_put_contents($tmp, $jpeg);
    $file = new UploadedFile($tmp, 'foto.jpg', 'image/jpeg', null, true);

    $img = app(StoreArticleImage::class)->handle($this->article, $file);

    // 1000x500 rodada = 500x1000 (retrato); largura 500 -> thumb 400x800.
    expect(largura($img->thumb_path))->toBe([400, 800, 'image/webp']);
});

it('a primeira fica principal e marcar outra desmarca a anterior', function () {
    carregar($this->article, [imagem('a.jpg'), imagem('b.jpg')]);

    [$a, $b] = $this->article->images()->get()->all();
    expect($a->is_primary)->toBeTrue()->and($b->is_primary)->toBeFalse()
        ->and([$a->position, $b->position])->toBe([1, 2]);

    gerir($this->article)->callTableAction('principal', $b);

    expect($a->refresh()->is_primary)->toBeFalse()
        ->and($b->refresh()->is_primary)->toBeTrue()
        ->and($this->article->images()->where('is_primary', true)->count())->toBe(1)
        ->and($this->article->primaryImage->id)->toBe($b->id);
});

it('reordena as imagens', function () {
    carregar($this->article, [imagem('a.jpg'), imagem('b.jpg'), imagem('c.jpg')]);
    $ids = $this->article->images()->pluck('id')->all();

    gerir($this->article)->call('reorderTable', array_reverse($ids));

    expect($this->article->images()->pluck('id')->all())->toBe(array_reverse($ids));
});

it('apagar a imagem remove os ficheiros e promove outra a principal', function () {
    carregar($this->article, [imagem('a.jpg'), imagem('b.jpg')]);
    [$a, $b] = $this->article->images()->get()->all();

    gerir($this->article)->callTableAction('delete', $a);

    Storage::disk('local')->assertMissing($a->original_path);
    Storage::disk('public')->assertMissing($a->thumb_path);
    Storage::disk('public')->assertMissing($a->screen_path);
    expect($b->refresh()->is_primary)->toBeTrue();
});

it('apagar o artigo remove todos os ficheiros', function () {
    carregar($this->article, [imagem('a.jpg'), imagem('b.jpg')]);
    $imagens = $this->article->images()->get();

    $this->article->delete();

    foreach ($imagens as $img) {
        Storage::disk('local')->assertMissing($img->original_path);
        Storage::disk('public')->assertMissing($img->thumb_path);
        Storage::disk('public')->assertMissing($img->screen_path);
    }
    expect(ArticleImage::query()->count())->toBe(0);
});

it('recusa ficheiros acima de 10 MB com mensagem em português', function () {
    $grande = UploadedFile::fake()->image('grande.jpg', 100, 100)->size(10241);

    $t = carregar($this->article, [$grande])->assertHasFormErrors(['files']);

    expect(erros($t))->toContain('Cada imagem pode ter no máximo 10 MB.');

    expect($this->article->images()->count())->toBe(0);
});

it('aceita um ficheiro de exactamente 10 MB', function () {
    $limite = UploadedFile::fake()->image('limite.jpg', 100, 100)->size(10240);

    carregar($this->article, [$limite])->assertHasNoFormErrors();

    expect($this->article->images()->count())->toBe(1);
});

it('recusa tipos que não sejam JPEG, PNG ou WebP', function () {
    $gif = UploadedFile::fake()->image('anim.gif', 100, 100);
    $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    foreach ([$gif, $pdf] as $ficheiro) {
        $t = carregar($this->article, [$ficheiro])->assertHasFormErrors(['files']);
        expect(erros($t))->toContain('Só são aceites imagens JPEG, PNG ou WebP.');
    }

    expect($this->article->images()->count())->toBe(0);
});

it('mostra a miniatura da principal na lista de artigos', function () {
    carregar($this->article, [imagem('a.jpg')]);

    Livewire::test(ListArticles::class)
        ->assertCanSeeTableRecords([$this->article])
        ->assertTableColumnStateSet('primaryImage.thumb_url', $this->article->fresh()->primaryImage->thumb_url, $this->article);
});

/** JPEG truncado: cabeçalho legível (passa no MIME) mas dados em falta, não descodifica. */
function corrompida(): UploadedFile
{
    $im = imagecreatetruecolor(800, 600);
    for ($i = 0; $i < 3000; $i++) {
        imagesetpixel($im, random_int(0, 799), random_int(0, 599), random_int(0, 0xFFFFFF));
    }
    ob_start();
    imagejpeg($im);

    return UploadedFile::fake()->createWithContent('estragada.jpg', substr((string) ob_get_clean(), 0, 300));
}

it('recusa imagem corrompida com erro de formulário e sem gravar nada, nem do lote', function () {
    $t = carregar($this->article, [imagem('boa.jpg'), corrompida()])->assertHasFormErrors(['files']);

    expect(erros($t))->toContain('corrompido');

    expect($this->article->images()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('o lote é tudo-ou-nada mesmo se a falha só aparece ao processar', function () {
    $store = app(StoreArticleImage::class);

    expect(fn () => $store->handleMany($this->article, [imagem('boa.jpg'), corrompida()]))
        ->toThrow(InvalidArgumentException::class);

    expect($this->article->images()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('apaga os ficheiros se a base de dados falhar', function () {
    ArticleImage::creating(fn () => throw new RuntimeException('BD em baixo'));

    expect(fn () => app(StoreArticleImage::class)->handle($this->article, imagem('a.jpg')))
        ->toThrow(RuntimeException::class);

    expect(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('recusa imagens com píxeis a mais', function () {
    // Só cabeçalho PNG a declarar 4001x4001 (16,008 MP > MAX_PIXELS): evita alocar a imagem no teste.
    $ihdr = pack('NN', 4001, 4001)."\x08\x02\x00\x00\x00";
    $enorme = UploadedFile::fake()->createWithContent('enorme.png', "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr)));

    $t = carregar($this->article, [$enorme])->assertHasFormErrors(['files']);

    expect(erros($t))->toContain('demasiados píxeis');

    expect($this->article->images()->count())->toBe(0);
});

it('gera os derivados com a qualidade WebP configurada', function () {
    $im = imagecreatetruecolor(300, 300);
    for ($i = 0; $i < 20000; $i++) {
        imagesetpixel($im, random_int(0, 299), random_int(0, 299), random_int(0, 0xFFFFFF));
    }
    $tmp = tempnam(sys_get_temp_dir(), 'q');
    imagepng($im, $tmp);
    $file = new UploadedFile($tmp, 'ruido.png', 'image/png', null, true);

    $img = app(StoreArticleImage::class)->handle($this->article, $file);

    $webp = function (int $q) use ($im): int {
        ob_start();
        imagewebp($im, null, $q);

        return strlen((string) ob_get_clean());
    };
    $real = strlen(Storage::disk('public')->get($img->thumb_path));

    expect(StoreArticleImage::QUALITY)->toBe(80)
        ->and(abs($real - $webp(80)))->toBeLessThan(abs($real - $webp(10)));
});

it('corrige todas as orientações EXIF (rotações e espelhos)', function (int $orientacao, int $w, int $h, string $canto) {
    // 100x50 branca com um bloco vermelho 20x20 no canto superior esquerdo da imagem gravada.
    $im = imagecreatetruecolor(100, 50);
    imagefill($im, 0, 0, 0xFFFFFF);
    imagefilledrectangle($im, 0, 0, 19, 19, 0xFF0000);
    $tmp = tempnam(sys_get_temp_dir(), 'exif');
    imagejpeg($im, $tmp, 100);
    $exif = "Exif\0\0".'MM'."\x00\x2a\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01\x00".chr($orientacao)."\x00\x00"."\x00\x00\x00\x00";
    $jpeg = (string) file_get_contents($tmp);
    file_put_contents($tmp, substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2));

    $img = app(StoreArticleImage::class)->handle($this->article, new UploadedFile($tmp, 'f.jpg', 'image/jpeg', null, true));

    $out = imagecreatefromstring(Storage::disk('public')->get($img->thumb_path));
    expect([imagesx($out), imagesy($out)])->toBe([$w, $h]);
    // Onde o canto superior esquerdo original deve ter ido parar.
    [$x, $y] = match ($canto) {
        'tl' => [5, 5],
        'tr' => [$w - 6, 5],
        'bl' => [5, $h - 6],
        'br' => [$w - 6, $h - 6],
    };
    $rgb = imagecolorat($out, $x, $y);
    expect(($rgb >> 16) & 255)->toBeGreaterThan(200)
        ->and(($rgb >> 8) & 255)->toBeLessThan(80);
})->with([
    'espelho horizontal (2)' => [2, 100, 50, 'tr'],
    'rodada 180 (3)' => [3, 100, 50, 'br'],
    'espelho vertical (4)' => [4, 100, 50, 'bl'],
    'transposta (5)' => [5, 50, 100, 'tl'],
    'rodar 90 horário (6)' => [6, 50, 100, 'tr'],
    'transversa (7)' => [7, 50, 100, 'br'],
    'rodar 90 anti-horário (8)' => [8, 50, 100, 'bl'],
]);

it('marcar principal deixa sempre exactamente uma, mesmo repetida', function () {
    carregar($this->article, [imagem('a.jpg'), imagem('b.jpg'), imagem('c.jpg')]);
    [$a, $b, $c] = $this->article->images()->get()->all();

    $b->makePrimary();
    $c->makePrimary();
    $b->makePrimary();
    $b->makePrimary();

    expect($this->article->images()->where('is_primary', true)->pluck('id')->all())->toBe([$b->id]);
});
