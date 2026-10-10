<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Dados de exemplo do protótipo estático (sem BD). Tudo fictício.
 *
 * Regra D5: os preços só saem daqui para a view quando o modo o permite —
 * a remoção é feita no servidor (artigosParaPublica), nunca com CSS.
 */
final class PrototipoDados
{
    public const VENDEDORA = 'Rita Sousa';

    public const VENDEDORA_EMAIL = 'rita.sousa@example.com';

    public const MODOS = ['com-precos', 'sem-precos', 'so-imagens'];

    /** Estados de cada ecrã; o primeiro é o de omissão. */
    public const ESTADOS = [
        'indice' => ['cheio'],
        'login' => ['vazio', 'a-carregar', 'erro'],
        'recuperar' => ['passo-1', 'passo-2', 'a-carregar', 'erro-email', 'erro-coincidem', 'expirada', 'enviado'],
        'artigos' => ['cheio', 'a-carregar', 'vazio', 'vazio-filtro', 'erro', 'cheio-sem-rede'],
        'artigo' => ['cheio', 'a-carregar', 'vazio', 'erro'],
        'partilhar' => ['form', 'a-carregar', 'erro-sem-rede', 'erro-falhou', 'vazio', 'gerada'],
        'offline' => ['vazio', 'a-carregar', 'interrompida', 'sem-espaco', 'cheio', 'cheio-sem-rede'],
        'publica' => ['cheio', 'a-carregar', 'vazio', 'expirada', 'revogada', 'inexistente'],
    ];

    private const QUANTIDADES = [50 => 1.0, 100 => 0.92, 250 => 0.85, 500 => 0.8, 1000 => 0.75];

    private const C = [
        'marinho' => ['nome' => 'Marinho', 'hex' => '#1E3A5F'],
        'preto' => ['nome' => 'Preto', 'hex' => '#1F1F1F'],
        'branco' => ['nome' => 'Branco', 'hex' => '#F7F7F5'],
        'vermelho' => ['nome' => 'Vermelho', 'hex' => '#B8322A'],
        'verde' => ['nome' => 'Verde', 'hex' => '#3E7A4E'],
        'azul' => ['nome' => 'Azul', 'hex' => '#2F6DB5'],
        'amarelo' => ['nome' => 'Amarelo', 'hex' => '#E3B53B'],
        'cinza' => ['nome' => 'Cinza', 'hex' => '#8A8F98'],
        'natural' => ['nome' => 'Natural', 'hex' => '#D9C9A8'],
        'laranja' => ['nome' => 'Laranja', 'hex' => '#E07A2F'],
        'rosa' => ['nome' => 'Rosa', 'hex' => '#D97A9C'],
        'prata' => ['nome' => 'Prata', 'hex' => '#BFC3C8'],
    ];

    /**
     * Definição compacta: [ref, nome, tipo, categoria, cores, tamanhos (tamanho => multiplicador),
     * preço base, imagens, campos, variantes sem escalões (true = todas; lista "Cor|Tamanho")].
     */
    private const DEF = [
        ['MRV-1001', 'Caneta esferográfica metálica', 'Escrita', 'Canetas e escrita', ['marinho', 'preto', 'prata', 'vermelho'], [], 0.95, 6,
            ['Material' => 'Alumínio', 'Tinta' => 'Azul', 'Mecanismo' => 'Botão', 'Área de marcação' => '40 × 6 mm', 'Técnica' => 'Gravação laser', 'Peso' => '18 g', 'Embalagem' => 'Caixa de 50']],
        ['MRV-1002', 'Caneta de bambu ecológica', 'Escrita', 'Canetas e escrita', ['natural'], [], 0.62, 4,
            ['Material' => 'Bambu', 'Tinta' => 'Azul', 'Mecanismo' => 'Rotativo', 'Área de marcação' => '35 × 7 mm', 'Técnica' => 'Gravação laser', 'Certificação' => 'FSC', 'Embalagem' => 'Saco de 100']],
        ['MRV-1003', 'Lápis de grafite com borracha', 'Escrita', 'Canetas e escrita', ['amarelo', 'verde', 'azul'], [], 0.21, 3,
            ['Material' => 'Madeira de cedro', 'Dureza' => 'HB', 'Comprimento' => '175 mm', 'Área de marcação' => '50 × 5 mm', 'Técnica' => 'Tampografia', 'Embalagem' => 'Caixa de 144']],
        ['MRV-1004', 'Marcador fluorescente', 'Escrita', 'Canetas e escrita', ['amarelo', 'laranja', 'rosa', 'verde'], [], 0.48, 2,
            ['Ponta' => 'Biselada 1-5 mm', 'Tinta' => 'À base de água', 'Corpo' => 'Plástico reciclado', 'Área de marcação' => '45 × 8 mm', 'Técnica' => 'Tampografia', 'Embalagem' => 'Caixa de 100']],
        ['MRV-1005', 'Garrafa térmica de aço inox', 'Garrafas e copos', 'Garrafas e copos', ['marinho', 'preto', 'branco', 'verde'], ['500 ml' => 1.0, '750 ml' => 1.25], 7.9, 6,
            ['Material' => 'Aço inoxidável 18/8', 'Parede' => 'Dupla, isolamento a vácuo', 'Mantém frio' => '24 h', 'Mantém quente' => '12 h', 'Tampa' => 'Rosca com pega', 'Sem BPA' => 'Sim', 'Lavagem' => 'À mão', 'Área de marcação' => '60 × 80 mm', 'Técnica' => 'Gravação laser', 'Peso' => '340 g', 'Embalagem' => 'Caixa individual', 'Prazo de produção' => '10 dias úteis'],
            ['Branco|750 ml']],
        ['MRV-1006', 'Garrafa de alumínio desportiva', 'Garrafas e copos', 'Garrafas e copos', ['vermelho', 'azul', 'prata'], ['600 ml' => 1.0], 2.85, 5,
            ['Material' => 'Alumínio', 'Tampa' => 'Mosquetão', 'Sem BPA' => 'Sim', 'Área de marcação' => '50 × 70 mm', 'Técnica' => 'Serigrafia', 'Peso' => '110 g', 'Embalagem' => 'Saco individual']],
        ['MRV-1007', 'Copo de café reutilizável', 'Garrafas e copos', 'Garrafas e copos', ['preto', 'cinza', 'natural'], ['350 ml' => 1.0, '450 ml' => 1.15], 3.4, 4,
            ['Material' => 'Polipropileno e fibra de bambu', 'Tampa' => 'Silicone', 'Micro-ondas' => 'Não', 'Máquina de lavar' => 'Sim', 'Área de marcação' => '70 × 50 mm', 'Técnica' => 'Tampografia', 'Embalagem' => 'Caixa individual', 'Prazo de produção' => '12 dias úteis']],
        ['MRV-1008', 'Caneca de cerâmica clássica', 'Garrafas e copos', 'Garrafas e copos', ['branco', 'marinho', 'vermelho'], ['300 ml' => 1.0], 2.1, 3,
            ['Material' => 'Cerâmica', 'Acabamento' => 'Brilhante', 'Máquina de lavar' => 'Sim', 'Área de marcação' => '200 × 80 mm', 'Técnica' => 'Sublimação', 'Embalagem' => 'Caixa de 36']],
        ['MRV-1009', 'Saco de algodão reutilizável', 'Sacos', 'Sacos e mochilas', ['natural', 'preto', 'marinho'], [], 1.35, 5,
            ['Material' => 'Algodão 140 g/m²', 'Dimensões' => '38 × 42 cm', 'Asas' => 'Compridas, 70 cm', 'Área de marcação' => '25 × 30 cm', 'Técnica' => 'Serigrafia', 'Lavagem' => 'Até 30 °C', 'Embalagem' => 'Saco de 100']],
        ['MRV-1010', 'Saco de compras dobrável', 'Sacos', 'Sacos e mochilas', ['verde', 'azul', 'vermelho', 'preto'], [], 0.9, 2,
            ['Material' => 'Poliéster 190T', 'Dimensões' => '40 × 38 cm', 'Dobrado' => '9 × 9 cm', 'Área de marcação' => '20 × 20 cm', 'Técnica' => 'Serigrafia', 'Embalagem' => 'Bolsa individual']],
        ['MRV-1011', 'Mochila para portátil 15"', 'Sacos', 'Sacos e mochilas', ['preto', 'cinza'], [], 18.5, 8,
            ['Material' => 'Poliéster 600D', 'Compartimento' => 'Portátil até 15,6"', 'Capacidade' => '20 l', 'Bolsos' => '4', 'Porta USB' => 'Sim', 'Área de marcação' => '15 × 10 cm', 'Técnica' => 'Bordado', 'Peso' => '850 g', 'Garantia' => '2 anos', 'Embalagem' => 'Saco individual'],
            ['Cinza']],
        ['MRV-1012', 'Saco mochila com cordões', 'Sacos', 'Sacos e mochilas', ['vermelho', 'azul', 'amarelo', 'preto', 'branco'], [], 0.75, 0,
            ['Material' => 'Poliéster 210D', 'Dimensões' => '34 × 42 cm', 'Fecho' => 'Cordões', 'Área de marcação' => '25 × 25 cm', 'Técnica' => 'Serigrafia', 'Embalagem' => 'Saco de 100']],
        ['MRV-1013', 'T-shirt de algodão unissexo', 'Têxtil', 'Têxtil', ['branco', 'preto', 'marinho', 'vermelho', 'cinza'], ['S' => 1.0, 'M' => 1.0, 'L' => 1.0, 'XL' => 1.08, 'XXL' => 1.15], 3.6, 6,
            ['Material' => 'Algodão 150 g/m²', 'Corte' => 'Unissexo', 'Gola' => 'Redonda', 'Costuras' => 'Duplas', 'Etiqueta' => 'Destacável', 'Área de marcação' => '30 × 40 cm', 'Técnica' => 'Serigrafia ou DTF', 'Lavagem' => 'Até 40 °C', 'Certificação' => 'OEKO-TEX', 'Embalagem' => 'Saco individual']],
        ['MRV-1014', 'Polo piqué de manga curta', 'Têxtil', 'Têxtil', ['marinho', 'branco', 'preto'], ['S' => 1.0, 'M' => 1.0, 'L' => 1.0, 'XL' => 1.1], 8.4, 4,
            ['Material' => 'Algodão piqué 210 g/m²', 'Botões' => '3, a condizer', 'Gola' => 'Malha canelada', 'Área de marcação' => '10 × 10 cm', 'Técnica' => 'Bordado', 'Lavagem' => 'Até 40 °C', 'Embalagem' => 'Saco individual'],
            ['Preto|XL']],
        ['MRV-1015', 'Sweatshirt com capuz', 'Têxtil', 'Têxtil', ['cinza', 'preto', 'marinho'], ['S' => 1.0, 'M' => 1.0, 'L' => 1.0, 'XL' => 1.08], 14.9, 5,
            ['Material' => '80 % algodão, 20 % poliéster', 'Gramagem' => '280 g/m²', 'Bolso' => 'Canguru', 'Interior' => 'Felpa', 'Área de marcação' => '28 × 30 cm', 'Técnica' => 'Serigrafia', 'Lavagem' => 'Até 30 °C', 'Embalagem' => 'Saco individual']],
        ['MRV-1016', 'Boné de cinco painéis', 'Têxtil', 'Têxtil', ['preto', 'marinho', 'branco', 'vermelho'], [], 2.95, 3,
            ['Material' => 'Algodão escovado', 'Fecho' => 'Velcro', 'Painéis' => '5', 'Área de marcação' => '8 × 5 cm', 'Técnica' => 'Bordado', 'Embalagem' => 'Caixa de 50']],
        ['MRV-1017', 'Caderno A5 com capa rígida', 'Papelaria', 'Cadernos e papelaria', ['marinho', 'preto', 'vermelho', 'verde'], [], 3.2, 6,
            ['Formato' => 'A5', 'Folhas' => '96, pautadas', 'Papel' => '80 g/m²', 'Capa' => 'Rígida, tacto suave', 'Fecho' => 'Elástico', 'Marcador' => 'Fita', 'Área de marcação' => '90 × 120 mm', 'Técnica' => 'Gravação a seco', 'Embalagem' => 'Caixa de 40']],
        ['MRV-1018', 'Bloco de notas de papel reciclado', 'Papelaria', 'Cadernos e papelaria', ['natural'], [], 1.1, 2,
            ['Formato' => 'A6', 'Folhas' => '70, lisas', 'Papel' => 'Reciclado 70 g/m²', 'Encadernação' => 'Argolas', 'Área de marcação' => '70 × 90 mm', 'Técnica' => 'Impressão digital']],
        ['MRV-1019', 'Agenda semanal 2027', 'Papelaria', 'Cadernos e papelaria', ['marinho', 'preto'], [], 5.75, 4,
            ['Formato' => '17 × 24 cm', 'Distribuição' => 'Semana em duas páginas', 'Papel' => '70 g/m²', 'Capa' => 'Pele sintética', 'Área de marcação' => '80 × 50 mm', 'Técnica' => 'Gravação a quente', 'Idioma' => 'Português', 'Embalagem' => 'Caixa de 20'],
            true],
        ['MRV-1020', 'Bateria externa 10 000 mAh', 'Tecnologia', 'Tecnologia', ['preto', 'branco'], [], 12.4, 5,
            ['Capacidade' => '10 000 mAh', 'Saídas' => 'USB-A e USB-C', 'Carregamento rápido' => '20 W', 'Material' => 'Plástico ABS', 'Indicador' => 'LED de carga', 'Área de marcação' => '50 × 30 mm', 'Técnica' => 'Tampografia', 'Peso' => '210 g', 'Garantia' => '2 anos', 'Embalagem' => 'Caixa individual']],
        ['MRV-1021', 'Pen USB de 32 GB', 'Tecnologia', 'Tecnologia', ['prata', 'preto', 'azul'], ['16 GB' => 0.85, '32 GB' => 1.0, '64 GB' => 1.4], 4.3, 3,
            ['Interface' => 'USB 3.0', 'Corpo' => 'Metal', 'Velocidade de leitura' => 'Até 80 MB/s', 'Área de marcação' => '25 × 10 mm', 'Técnica' => 'Gravação laser', 'Embalagem' => 'Caixa individual']],
        ['MRV-1022', 'Carregador sem fios de secretária', 'Tecnologia', 'Tecnologia', ['preto', 'natural'], [], 9.8, 0,
            ['Potência' => '15 W', 'Compatibilidade' => 'Qi', 'Material' => 'Bambu e plástico', 'Cabo' => 'USB-C incluído', 'Área de marcação' => '40 × 40 mm', 'Técnica' => 'Gravação laser', 'Embalagem' => 'Caixa individual'],
            true],
        ['MRV-1023', 'Porta-chaves de metal', 'Acessórios', 'Acessórios', ['prata'], [], 0.85, 2,
            ['Material' => 'Liga de zinco', 'Argola' => '25 mm', 'Área de marcação' => '30 × 15 mm', 'Técnica' => 'Gravação laser', 'Peso' => '22 g', 'Embalagem' => 'Saco individual']],
        ['MRV-1024', 'Fita porta-crachá (lanyard)', 'Acessórios', 'Acessórios', ['marinho', 'preto', 'vermelho', 'verde', 'azul'], [], 0.55, 3,
            ['Material' => 'Poliéster', 'Largura' => '20 mm', 'Fecho' => 'Mosquetão', 'Segurança' => 'Fecho anti-estrangulamento', 'Área de marcação' => 'Toda a fita', 'Técnica' => 'Sublimação', 'Embalagem' => 'Saco de 100']],
        ['MRV-1025', 'Guarda-chuva dobrável', 'Acessórios', 'Acessórios', ['preto', 'marinho', 'vermelho'], [], 6.9, 4,
            ['Diâmetro' => '96 cm', 'Abertura' => 'Automática', 'Varetas' => '8, fibra de vidro', 'Tecido' => 'Pongee', 'Área de marcação' => '25 × 15 cm por gomo', 'Técnica' => 'Serigrafia', 'Peso' => '320 g', 'Embalagem' => 'Bolsa individual']],
        ['MRV-1026', 'Avental de cozinha', 'Têxtil', 'Têxtil', ['preto', 'natural', 'vermelho'], [], 4.1, 0,
            ['Material' => 'Algodão e poliéster', 'Dimensões' => '70 × 90 cm', 'Bolsos' => '1, frontal', 'Atilhos' => 'Reguláveis', 'Área de marcação' => '25 × 25 cm', 'Técnica' => 'Serigrafia', 'Lavagem' => 'Até 40 °C']],
    ];

    /** Refs de exemplo para a selecção (ecrã 5) e para a partilha pública (ecrã 7). */
    public const SELECCAO_EXEMPLO = ['MRV-1005', 'MRV-1013', 'MRV-1017'];

    public const PARTILHA_EXEMPLO = ['MRV-1005', 'MRV-1001', 'MRV-1013', 'MRV-1017', 'MRV-1011'];

    /** @return list<array<string, mixed>> */
    public static function artigos(): array
    {
        return array_map(self::montar(...), self::DEF);
    }

    /** @return array<string, mixed>|null */
    public static function artigo(string $ref): ?array
    {
        foreach (self::artigos() as $artigo) {
            if ($artigo['ref'] === $ref) {
                return $artigo;
            }
        }

        return null;
    }

    public static function precosVisiveisPermitidos(string $modo): bool
    {
        return $modo === 'com-precos';
    }

    /**
     * Artigos para a página pública, já filtrados no servidor (D5):
     * fora de «com-precos» não leva nenhum valor de preço.
     *
     * @param  list<string>  $refs
     * @return list<array<string, mixed>>
     */
    public static function artigosParaPublica(string $modo, array $refs = self::PARTILHA_EXEMPLO): array
    {
        $artigos = array_values(array_filter(array_map(self::artigo(...), $refs)));

        if (self::precosVisiveisPermitidos($modo)) {
            return $artigos;
        }

        return array_map(self::semPrecos(...), $artigos);
    }

    /**
     * @param  array<string, mixed>  $artigo
     * @return array<string, mixed>
     */
    public static function semPrecos(array $artigo): array
    {
        $artigo['desde'] = null;
        $artigo['variantes'] = array_map(
            fn (array $v): array => ['cor' => $v['cor'], 'tamanho' => $v['tamanho']],
            $artigo['variantes'],
        );

        return $artigo;
    }

    /** @return list<string> */
    public static function estados(string $ecra): array
    {
        return self::ESTADOS[$ecra] ?? ['cheio'];
    }

    public static function estado(string $ecra, ?string $pedido): string
    {
        $estados = self::estados($ecra);

        return in_array($pedido, $estados, true) ? $pedido : $estados[0];
    }

    public static function euros(float $valor): string
    {
        return number_format($valor, 2, ',', "\u{00A0}")."\u{00A0}€";
    }

    /**
     * @param  array<int, mixed>  $d
     * @return array<string, mixed>
     */
    private static function montar(array $d): array
    {
        [$ref, $nome, $tipo, $categoria, $cores, $tamanhos, $base, $imagens, $campos] = $d;
        $semEscaloes = $d[9] ?? [];

        $variantes = [];
        foreach ($cores as $c) {
            $cor = self::C[$c]['nome'];
            foreach ($tamanhos ?: [null => 1.0] as $tamanho => $mult) {
                $tamanho = $tamanho === '' ? null : $tamanho;
                $chave = $tamanho === null ? $cor : $cor.'|'.$tamanho;
                $vazio = $semEscaloes === true || in_array($chave, (array) $semEscaloes, true);
                $variantes[] = [
                    'cor' => $cor,
                    'tamanho' => $tamanho,
                    'escaloes' => $vazio ? [] : self::escaloes($base * $mult),
                ];
            }
        }

        $precos = array_merge(...array_map(fn (array $v): array => array_column($v['escaloes'], 'preco'), $variantes));

        return [
            'ref' => $ref,
            'nome' => $nome,
            'tipo' => $tipo,
            'categoria' => $categoria,
            'cores' => array_map(fn (string $c): array => self::C[$c], $cores),
            'tamanhos' => array_keys($tamanhos),
            'variantes' => $variantes,
            'campos' => $campos,
            'imagens' => $imagens,
            'desde' => $precos === [] ? null : min($precos),
        ];
    }

    /** @return list<array{min: int, preco: float}> */
    private static function escaloes(float $preco): array
    {
        $lista = [];
        foreach (self::QUANTIDADES as $min => $factor) {
            $lista[] = ['min' => $min, 'preco' => round($preco * $factor, 2)];
        }

        return $lista;
    }
}
