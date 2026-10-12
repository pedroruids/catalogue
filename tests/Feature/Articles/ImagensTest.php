<?php

use App\Actions\StoreArticleImage;
use App\Filament\Resources\Articles\Pages\EditArticle;
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

function imagem(string $nome, int $w = 2000, int $h = 1000, string $tipo = 'jpeg'): UploadedFile
{
    return UploadedFile::fake()->image($nome, $w, $h);
}

function carregar(Article $article, array $files)
{
    return gerir($article)->callTableAction('create', data: ['files' => $files]);
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

    carregar($this->article, [$grande])->assertHasFormErrors(['files']);

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

    carregar($this->article, [$gif])->assertHasFormErrors(['files']);
    carregar($this->article, [$pdf])->assertHasFormErrors(['files']);

    expect($this->article->images()->count())->toBe(0);
});

it('mostra a miniatura da principal na lista de artigos', function () {
    carregar($this->article, [imagem('a.jpg')]);

    expect($this->article->fresh()->primaryImage->thumb_url)->not->toBeEmpty();
});
