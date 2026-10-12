<?php

use App\Enums\ArticleStatus;
use App\Enums\ShareMode;
use App\Models\Article;
use App\Models\Share;
use App\Models\User;
use App\Support\Selection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('junta e retira artigos na lista, com a contagem na app-bar', function () {
    $a = Article::factory()->published()->create(['name' => 'Caneca Azul']);
    $b = Article::factory()->published()->create(['name' => 'Lanyard Dourado']);

    Livewire::test('pages::articles.index')
        ->call('toggleSelection', $b->id)
        ->call('toggleSelection', $a->id)
        ->assertSee('aria-label="Selecção: 2 artigos"', false);

    expect(Selection::ids())->toBe([$b->id, $a->id]);

    Livewire::test('pages::articles.index')->call('toggleSelection', $b->id);

    expect(Selection::ids())->toBe([$a->id]);
});

it('junta e retira na página do artigo', function () {
    $article = Article::factory()->published()->create();

    Livewire::test('pages::articles.show', ['article' => $article])
        ->assertSee('Juntar à selecção')
        ->call('toggleSelection')
        ->assertSee('Na selecção');

    expect(Selection::ids())->toBe([$article->id]);

    Livewire::test('pages::articles.show', ['article' => $article])->call('toggleSelection');

    expect(Selection::ids())->toBe([]);
});

it('«Partilhar» na página do artigo junta-o e abre o ecrã de partilha', function () {
    $article = Article::factory()->published()->create();

    Livewire::test('pages::articles.show', ['article' => $article])
        ->call('share')
        ->assertRedirect(route('shares.create'));

    expect(Selection::ids())->toBe([$article->id]);
});

it('um rascunho não se junta e um artigo despublicado sai da selecção', function () {
    $draft = Article::factory()->create(['status' => ArticleStatus::Draft]);
    $article = Article::factory()->published()->create();

    Selection::toggle($draft->id);
    Selection::toggle($article->id);

    expect(Selection::ids())->toBe([$article->id]);

    $article->update(['status' => ArticleStatus::Draft]);

    expect(Selection::ids())->toBe([])->and(Selection::count())->toBe(0);
});

it('manda o convidado para o login', function () {
    auth()->logout();

    $this->get(route('shares.create'))->assertRedirect(route('login'));
});

it('mostra o estado vazio sem artigos na selecção', function () {
    Livewire::test('pages::shares.create')
        ->assertSee('Ainda não escolheu artigos')
        ->assertDontSee('Gerar ligação');
});

it('gera com as omissões (sem preços, 7 dias), pela ordem da selecção, e mostra o URL', function () {
    [$a, $b] = Article::factory()->published()->count(2)->create()->all();
    Selection::toggle($b->id);
    Selection::toggle($a->id);

    $test = Livewire::test('pages::shares.create')
        ->assertSet('mode', 'without_prices')->assertSet('days', 7)
        ->call('generate')
        ->assertHasNoErrors();

    $share = Share::query()->sole();

    expect($share->mode)->toBe(ShareMode::WithoutPrices)
        ->and($share->expires_at->isSameDay(now()->addDays(7)))->toBeTrue()
        ->and($share->articles()->pluck('articles.id')->all())->toBe([$b->id, $a->id])
        ->and($test->get('url'))->toStartWith(url('/p/'));

    $test->assertSee($test->get('url'))->assertSee('Sem preços')->assertSee('Válida até '.$share->expires_at->format('d/m/Y'));
    // A selecção mantém-se até o vendedor a limpar (D16).
    expect(Selection::ids())->toBe([$b->id, $a->id]);
});

it('respeita o modo e a validade escolhidos', function () {
    Selection::toggle(Article::factory()->published()->create()->id);

    Livewire::test('pages::shares.create')->set('mode', 'images_only')->set('days', 90)->call('generate');

    $share = Share::query()->sole();
    expect($share->mode)->toBe(ShareMode::ImagesOnly)->and($share->expires_at->isSameDay(now()->addDays(90)))->toBeTrue();
});

it('recusa validade e modo forjados, por campo', function () {
    Selection::toggle(Article::factory()->published()->create()->id);

    Livewire::test('pages::shares.create')->set('days', 15)->call('generate')
        ->assertHasErrors(['days'])->assertSee('A validade tem de ser de 7, 30 ou 90 dias.');
    Livewire::test('pages::shares.create')->set('mode', 'xpto')->call('generate')->assertHasErrors(['mode']);

    expect(Share::count())->toBe(0);
});

it('sem selecção não gera nada', function () {
    Livewire::test('pages::shares.create')->call('generate')->assertHasErrors(['articles']);

    expect(Share::count())->toBe(0);
});

it('um segundo «Gerar» não cria outra partilha', function () {
    Selection::toggle(Article::factory()->published()->create()->id);

    Livewire::test('pages::shares.create')->call('generate')->call('generate');

    expect(Share::count())->toBe(1);
});

it('retira artigos e limpa a selecção no ecrã de partilha', function () {
    [$a, $b] = Article::factory()->published()->count(2)->create()->all();
    Selection::toggle($a->id);
    Selection::toggle($b->id);

    Livewire::test('pages::shares.create')->call('remove', $a->id);
    expect(Selection::ids())->toBe([$b->id]);

    Livewire::test('pages::shares.create')->call('clearSelection')->assertSee('Ainda não escolheu artigos');
    expect(Selection::ids())->toBe([]);
});

it('o link gerado abre sem sessão e mostra os artigos', function () {
    $article = Article::factory()->published()->create(['name' => 'Caneca Visível']);
    Selection::toggle($article->id);

    $url = Livewire::test('pages::shares.create')->call('generate')->get('url');

    auth()->logout();
    $this->flushSession();

    $this->get($url)->assertOk()->assertSee('Caneca Visível');
});
