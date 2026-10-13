<?php

namespace Database\Seeders;

use App\Actions\StoreArticleImage;
use App\Enums\ArticleStatus;
use App\Enums\FieldType;
use App\Enums\Role;
use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\ProductType;
use App\Models\User;
use GdImage;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Dados de demonstração do Catalogue (cliente fictício «Maravilha») para rever a app no telemóvel.
 * Idempotente: chaves naturais (email, nome do tipo/campo, referência) + updateOrCreate; as imagens
 * só são geradas para artigos que ainda não têm nenhuma. Não apaga nada. Nunca usar em produção.
 *
 * Correr: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    /** Cor (nome do valor do eixo «Cor») → hex do fundo da imagem. */
    private const array COLORS = [
        'Azul' => '#2563eb', 'Preto' => '#27272a', 'Branco' => '#a1a1aa', 'Vermelho' => '#dc2626',
        'Verde' => '#16a34a', 'Natural' => '#b08968', 'Prateado' => '#71717a', 'Amarelo' => '#ca8a04',
        'Laranja' => '#ea580c', 'Rosa' => '#db2777', 'Cru' => '#a8a29e', 'Cinzento' => '#52525b',
    ];

    /** Fundos usados quando o artigo não tem eixo «Cor». */
    private const array FALLBACK = ['#0f766e', '#7c3aed', '#be123c'];

    public function run(): void
    {
        $this->users();

        foreach ($this->catalogue() as $typeSpec) {
            $this->type($typeSpec);
        }
    }

    private function users(): void
    {
        foreach ([['admin@maravilha.test', 'Administrador', Role::Admin], ['vendedor@maravilha.test', 'Vendedor Demo', Role::Vendedor]] as [$email, $name, $role]) {
            // Utilizadores existentes não se tocam (nem a password nem o papel).
            $user = User::query()->firstOrNew(['email' => $email]);

            if (! $user->exists) {
                $user->forceFill(['name' => $name, 'password' => 'password', 'role' => $role, 'email_verified_at' => now()])->save();
            }
        }
    }

    /**
     * @param  array{name: string, fields: list<array<string, mixed>>, articles: list<array<string, mixed>>}  $spec
     */
    private function type(array $spec): void
    {
        $type = ProductType::query()->firstOrCreate(['name' => $spec['name']]);

        $keys = [];
        foreach ($spec['fields'] as $position => $field) {
            $definition = $type->fieldDefinitions()->updateOrCreate(['name' => $field['name']], [
                'data_type' => $field['type'],
                'required' => false,
                'options' => $field['options'] ?? null,
                'position' => $position,
            ]);
            $keys[$field['name']] = $definition->key;
        }

        foreach ($spec['articles'] as $articleSpec) {
            $this->article($type, $keys, $articleSpec);
        }
    }

    /**
     * @param  array<string, string>  $keys  nome do campo → chave
     * @param  array<string, mixed>  $spec
     */
    private function article(ProductType $type, array $keys, array $spec): void
    {
        $values = [];
        foreach ($spec['values'] as $fieldName => $value) {
            $values[$keys[$fieldName]] = $value;
        }

        /** @var list<array{name: string, values: list<string>}> $axes */
        $axes = $spec['axes'] ?? [];

        $article = Article::query()->updateOrCreate(['reference' => $spec['reference']], [
            'product_type_id' => $type->id,
            'name' => $spec['name'],
            'description' => $spec['description'],
            'status' => $spec['published'] ?? true ? ArticleStatus::Published : ArticleStatus::Draft,
            'field_values' => $values,
            'variant_axes' => $axes === [] ? null : $axes,
        ]);

        $colors = $this->backgrounds($axes);
        $this->images($article, $spec['images'], $colors);
        $article->load('images');

        $this->variants($article, $axes, $spec['price'] ?? null);
    }

    /**
     * Fundos das imagens: um por valor de «Cor» se houver esse eixo, senão a paleta de recurso.
     *
     * @param  list<array{name: string, values: list<string>}>  $axes
     * @return list<string>
     */
    private function backgrounds(array $axes): array
    {
        foreach ($axes as $axis) {
            if ($axis['name'] === 'Cor') {
                return array_map(fn (string $c): string => self::COLORS[$c] ?? self::FALLBACK[0], $axis['values']);
            }
        }

        return self::FALLBACK;
    }

    /** @param  list<string>  $colors */
    private function images(Article $article, int $count, array $colors): void
    {
        if ($article->images()->exists()) {
            return;
        }

        $files = [$this->makeImage($article->name, 1, $count, $colors[0])];
        for ($i = 1; $i < $count; $i++) {
            $files[] = $this->makeImage($article->name, $i + 1, $count, $colors[$i % count($colors)]);
        }

        try {
            // Mesmo caminho de código do backoffice: original no disco privado + thumb/screen WebP.
            app(StoreArticleImage::class)->handleMany($article, $files);
        } finally {
            foreach ($files as $file) {
                @unlink((string) $file->getRealPath());
            }
        }
    }

    /**
     * @param  list<array{name: string, values: list<string>}>  $axes
     * @param  array{int, array<int, int>}|null  $price  [preço base em cêntimos, [qtd mínima => % do preço base]]
     */
    private function variants(Article $article, array $axes, ?array $price): void
    {
        if ($axes === []) {
            return; // sem variantes → «sob consulta»
        }

        $images = $article->images->values();
        $colorAxis = collect($axes)->search(fn (array $a): bool => $a['name'] === 'Cor');

        foreach ($this->combinations($axes) as $position => $options) {
            $image = $colorAxis === false ? null : $images[(int) array_search($options['Cor'], $axes[$colorAxis]['values'], true)] ?? null;

            /** @var ArticleVariant $variant */
            $variant = $article->variants()->updateOrCreate(
                ['reference' => $article->reference.'-'.Str::upper(Str::slug(implode('-', $options)))],
                ['options' => $options, 'article_image_id' => $image?->id, 'is_active' => true, 'position' => $position],
            );

            if ($price === null) {
                continue;
            }

            [$base, $tiers] = $price;
            // Pequena variação por posição para os preços «desde» não serem todos iguais.
            $unit = $base + ($position % 3) * 5;
            foreach ($tiers as $minQuantity => $percent) {
                $variant->priceTiers()->updateOrCreate(['min_quantity' => $minQuantity], ['unit_price_cents' => intdiv($unit * $percent, 100)]);
            }
        }
    }

    /**
     * Produto cartesiano dos eixos: [['Cor' => 'Azul', 'Tamanho' => 'M'], ...].
     *
     * @param  list<array{name: string, values: list<string>}>  $axes
     * @return list<array<string, string>>
     */
    private function combinations(array $axes): array
    {
        $result = [[]];
        foreach ($axes as $axis) {
            $next = [];
            foreach ($result as $partial) {
                foreach ($axis['values'] as $value) {
                    $next[] = $partial + [$axis['name'] => $value];
                }
            }
            $result = $next;
        }

        return $result;
    }

    private function makeImage(string $title, int $n, int $total, string $hex): UploadedFile
    {
        $image = imagecreatetruecolor(1600, 1200);
        if (! $image instanceof GdImage) {
            throw new RuntimeException('GD não conseguiu criar a imagem de demonstração.');
        }

        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        // Gradiente vertical simples (do tom base a um pouco mais escuro) para a imagem não ser uma mancha plana.
        for ($y = 0; $y < 1200; $y++) {
            $f = 1 - 0.35 * ($y / 1200);
            imageline($image, 0, $y, 1599, $y, (int) imagecolorallocate($image, min(255, max(0, (int) ($r * $f))), min(255, max(0, (int) ($g * $f))), min(255, max(0, (int) ($b * $f)))));
        }

        $white = (int) imagecolorallocate($image, 255, 255, 255);
        $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        $lines = explode("\n", wordwrap($title, 18, "\n", true));

        if (is_file($font)) {
            foreach ($lines as $i => $line) {
                imagettftext($image, 72, 0, 100, 480 + $i * 110, $white, $font, $line);
            }
            imagettftext($image, 40, 0, 100, 1100, $white, $font, "Maravilha · demo {$n}/{$total}");
        } else {
            imagestring($image, 5, 100, 500, $title." ({$n}/{$total})", $white);
        }

        $path = (string) tempnam(sys_get_temp_dir(), 'demo-img-');
        imagejpeg($image, $path, 90);
        unset($image);

        return new UploadedFile($path, Str::slug($title).'.jpg', 'image/jpeg', null, true);
    }

    /**
     * @return list<array{name: string, fields: list<array<string, mixed>>, articles: list<array<string, mixed>>}>
     */
    private function catalogue(): array
    {
        $tiers = [1 => 100, 100 => 90, 500 => 80, 1000 => 70];

        return [
            [
                'name' => 'Canetas',
                'fields' => [
                    ['name' => 'Material', 'type' => FieldType::Text],
                    ['name' => 'Tipo de escrita', 'type' => FieldType::Select, 'options' => ['Esferográfica', 'Roller', 'Marcador', 'Stylus']],
                    ['name' => 'Cor da tinta', 'type' => FieldType::Select, 'options' => ['Azul', 'Preta']],
                    ['name' => 'Recarregável', 'type' => FieldType::Boolean],
                ],
                'articles' => [
                    ['reference' => 'DEMO-CAN-001', 'name' => 'Caneta Esferográfica Bambu', 'description' => 'Caneta de corpo em bambu natural com clip metálico. Ideal para eventos sustentáveis.', 'images' => 3,
                        'values' => ['Material' => 'Bambu', 'Tipo de escrita' => 'Esferográfica', 'Cor da tinta' => 'Azul', 'Recarregável' => true],
                        'axes' => [['name' => 'Cor', 'values' => ['Natural', 'Preto', 'Verde']]], 'price' => [95, $tiers]],
                    ['reference' => 'DEMO-CAN-002', 'name' => 'Caneta Metálica Executiva', 'description' => 'Caneta em alumínio com acabamento acetinado e gravação laser incluída.', 'images' => 2,
                        'values' => ['Material' => 'Alumínio', 'Tipo de escrita' => 'Roller', 'Cor da tinta' => 'Preta', 'Recarregável' => true],
                        'axes' => [['name' => 'Cor', 'values' => ['Prateado', 'Preto', 'Azul', 'Vermelho']]], 'price' => [210, $tiers]],
                    ['reference' => 'DEMO-CAN-003', 'name' => 'Marcador Fluorescente', 'description' => 'Marcador de texto com ponta biselada. Cores vivas para destacar.', 'images' => 1,
                        'values' => ['Material' => 'Plástico reciclado', 'Tipo de escrita' => 'Marcador', 'Recarregável' => false],
                        'axes' => [['name' => 'Cor', 'values' => ['Amarelo', 'Laranja', 'Rosa']]], 'price' => [70, $tiers]],
                    ['reference' => 'DEMO-CAN-004', 'name' => 'Caneta Touch 2 em 1', 'description' => 'Esferográfica com ponta stylus para ecrãs tácteis.', 'images' => 2, 'published' => false,
                        'values' => ['Material' => 'Plástico ABS', 'Tipo de escrita' => 'Stylus', 'Cor da tinta' => 'Azul', 'Recarregável' => false]],
                ],
            ],
            [
                'name' => 'Garrafas e Termos',
                'fields' => [
                    ['name' => 'Capacidade (ml)', 'type' => FieldType::Number],
                    ['name' => 'Material', 'type' => FieldType::Text],
                    ['name' => 'Isolamento térmico', 'type' => FieldType::Boolean],
                    ['name' => 'Técnica de personalização', 'type' => FieldType::Select, 'options' => ['Gravação laser', 'Serigrafia', 'Tampografia']],
                ],
                'articles' => [
                    ['reference' => 'DEMO-GAR-001', 'name' => 'Garrafa Térmica Inox 500 ml', 'description' => 'Garrafa de parede dupla em aço inoxidável: 12 horas quente, 24 horas fria.', 'images' => 3,
                        'values' => ['Capacidade (ml)' => 500, 'Material' => 'Aço inoxidável 304', 'Isolamento térmico' => true, 'Técnica de personalização' => 'Gravação laser'],
                        'axes' => [['name' => 'Cor', 'values' => ['Prateado', 'Preto', 'Azul']]], 'price' => [890, $tiers]],
                    ['reference' => 'DEMO-GAR-002', 'name' => 'Garrafa Desporto Tritan 750 ml', 'description' => 'Garrafa leve sem BPA, com tampa de abertura rápida.', 'images' => 2,
                        'values' => ['Capacidade (ml)' => 750, 'Material' => 'Tritan', 'Isolamento térmico' => false, 'Técnica de personalização' => 'Tampografia'],
                        'axes' => [['name' => 'Cor', 'values' => ['Azul', 'Verde', 'Laranja']]], 'price' => [420, $tiers]],
                    ['reference' => 'DEMO-GAR-003', 'name' => 'Caneca de Viagem Isotérmica', 'description' => 'Caneca com tampa anti-derrame, cabe nos porta-copos do carro.', 'images' => 2,
                        'values' => ['Capacidade (ml)' => 380, 'Material' => 'Aço inoxidável', 'Isolamento térmico' => true, 'Técnica de personalização' => 'Serigrafia'],
                        'axes' => [['name' => 'Cor', 'values' => ['Preto', 'Branco']]], 'price' => [650, $tiers]],
                    ['reference' => 'DEMO-GAR-004', 'name' => 'Copo Reutilizável Eco', 'description' => 'Copo de fibra de arroz, 100% reutilizável. Preço sob consulta conforme a tiragem.', 'images' => 1,
                        'values' => ['Capacidade (ml)' => 350, 'Material' => 'Fibra de arroz', 'Isolamento térmico' => false, 'Técnica de personalização' => 'Serigrafia']],
                ],
            ],
            [
                'name' => 'Sacos e Têxteis',
                'fields' => [
                    ['name' => 'Material', 'type' => FieldType::Text],
                    ['name' => 'Gramagem (g/m²)', 'type' => FieldType::Number],
                    ['name' => 'Dimensões', 'type' => FieldType::Text],
                ],
                'articles' => [
                    ['reference' => 'DEMO-TEX-001', 'name' => 'Saco Tote de Algodão', 'description' => 'Saco de compras em algodão cru, asas longas. Muito espaço para a mensagem da marca.', 'images' => 3,
                        'values' => ['Material' => 'Algodão', 'Gramagem (g/m²)' => 140, 'Dimensões' => '38 x 42 cm'],
                        'axes' => [['name' => 'Cor', 'values' => ['Cru', 'Preto', 'Azul']]], 'price' => [180, $tiers]],
                    ['reference' => 'DEMO-TEX-002', 'name' => 'T-shirt Algodão Unissexo', 'description' => 'T-shirt de gola redonda, algodão penteado. Disponível em vários tamanhos.', 'images' => 3,
                        'values' => ['Material' => 'Algodão penteado', 'Gramagem (g/m²)' => 150, 'Dimensões' => 'Tamanhos S a XL'],
                        'axes' => [['name' => 'Cor', 'values' => ['Branco', 'Preto', 'Azul']], ['name' => 'Tamanho', 'values' => ['S', 'M', 'L', 'XL']]], 'price' => [390, $tiers]],
                    ['reference' => 'DEMO-TEX-003', 'name' => 'Mochila de Cordão', 'description' => 'Mochila leve em poliéster com cordões reforçados.', 'images' => 2,
                        'values' => ['Material' => 'Poliéster 210D', 'Dimensões' => '34 x 42 cm'],
                        'axes' => [['name' => 'Cor', 'values' => ['Preto', 'Vermelho', 'Cinzento']]], 'price' => [120, $tiers]],
                    ['reference' => 'DEMO-TEX-004', 'name' => 'Saco Térmico de Almoço', 'description' => 'Saco isotérmico com fecho e pega. Preço sob consulta.', 'images' => 1,
                        'values' => ['Material' => 'Poliéster com forro térmico', 'Dimensões' => '22 x 17 x 15 cm']],
                ],
            ],
        ];
    }
}
