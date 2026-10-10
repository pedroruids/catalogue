<?php

use App\Support\PrototipoDados;
use Illuminate\Support\Facades\Route;

/*
 * Ecrãs 1 (Entrar) e 2 (Recuperar password) do protótipo: cada estado dá 200 e mostra o seu texto-chave.
 * As rotas do protótipo só existem em `local`; os testes correm em `testing` e simulam o arranque.
 */
beforeEach(function () {
    $this->withoutVite();
    app()->detectEnvironment(fn () => 'local');
    Route::middleware('web')->group(base_path('routes/prototipo.php'));
    app('router')->getRoutes()->refreshNameLookups();
});

test('os estados do teste são os do PrototipoDados', function () {
    expect(PrototipoDados::ESTADOS['login'])->toBe(['vazio', 'a-carregar', 'erro'])
        ->and(PrototipoDados::ESTADOS['recuperar'])
        ->toBe(['passo-1', 'passo-2', 'a-carregar', 'erro-email', 'erro-coincidem', 'expirada', 'enviado']);
});

test('cada estado do login mostra o seu texto', function (string $estado, array $vê, array $nãoVê) {
    $this->get("/prototipo/login?estado={$estado}")
        ->assertOk()
        ->assertSee('Use o email e a password da sua conta.')
        ->assertSee(route('prototipo.recuperar'), false)
        ->assertSeeInOrder($vê, false)
        ->assertDontSee($nãoVê, false);
})->with([
    'vazio' => ['vazio', ['Entrar', 'nome@example.com', 'Esqueci-me da password'], ['Email ou password errados']],
    'a-carregar' => ['a-carregar', ['rita.sousa@example.com', 'A entrar…'], ['Email ou password errados']],
    'erro' => ['erro', ['role="alert"', 'Email ou password errados. Confirme e tente outra vez.', 'aria-invalid="true"', 'Verifique a password. Atenção às maiúsculas.'], []],
]);

test('o login no estado a-carregar tem os campos desactivados', function () {
    $html = $this->get('/prototipo/login?estado=a-carregar')->assertOk()->getContent();

    // atributo `disabled` solto (não as classes `disabled:`): email, password e botão
    expect(preg_match_all('/\sdisabled(?=[\s>])/', $html))->toBe(3);
    expect(preg_match_all('/\sdisabled(?=[\s>])/', $this->get('/prototipo/login')->getContent()))->toBe(0);
});

test('cada estado da recuperação mostra o seu texto', function (string $estado, array $vê, array $nãoVê) {
    $this->get("/prototipo/recuperar?estado={$estado}")
        ->assertOk()
        ->assertSeeInOrder($vê, false)
        ->assertDontSee($nãoVê, false);
})->with([
    'passo-1' => ['passo-1', ['Passo 1 de 2', 'Recuperar password', 'Enviar ligação', 'Voltar a entrar'], ['Nova password', 'O email parece incompleto']],
    'passo-2' => ['passo-2', ['Passo 2 de 2', 'Nova password', 'Pelo menos 8 caracteres.', 'Confirmar password', 'Guardar password', 'Voltar a entrar'], ['As passwords não coincidem']],
    'a-carregar' => ['a-carregar', ['Passo 1 de 2', 'rita.sousa@example.com', 'A enviar…'], ['O email parece incompleto']],
    'erro-email' => ['erro-email', ['aria-invalid="true"', 'role="alert"', 'O email parece incompleto. Exemplo: nome@example.com'], []],
    'erro-coincidem' => ['erro-coincidem', ['Nova password', 'aria-invalid="true"', 'role="alert"', 'As passwords não coincidem. Escreva a mesma nos dois campos.'], []],
    'expirada' => ['expirada', ['Esta ligação já expirou', 'As ligações valem 60 minutos. Peça uma nova e use-a logo.', 'Pedir nova ligação'], ['Passo 1 de 2']],
    'enviado' => ['enviado', ['Veja o seu email', 'Se o email existir, enviámos uma ligação.', 'Procure também no spam. A ligação vale 60 minutos.', 'Voltar a entrar'], ['Passo 1 de 2', 'Enviar ligação']],
]);

test('voltar a entrar e o fim da recuperação levam ao login', function () {
    $this->get('/prototipo/recuperar?estado=enviado')->assertSee(route('prototipo.login'), false);
    $this->get('/prototipo/recuperar?estado=passo-1')->assertSee(route('prototipo.login'), false);
});
