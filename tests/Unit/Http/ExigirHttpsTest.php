<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Http;

use Escala\Http\Manipulador;
use Escala\Http\Middleware\ExigirHttps;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExigirHttpsTest extends TestCase
{
    #[Test]
    public function acessoPorHttpVaiParaOMesmoEnderecoEmHttps(): void
    {
        $resposta = $this->middleware(conexaoSegura: false, forcar: true)
            ->tratar(new Requisicao('GET', cabecalhos: ['host' => 'escala.exemplo.com'], uri: '/escala.php?month=3'));

        self::assertInstanceOf(Redirecionamento::class, $resposta);
        self::assertSame('https://escala.exemplo.com/escala.php?month=3', $resposta->destino);
    }

    #[Test]
    public function semForcarOuJaEmHttpsSegueNormalmente(): void
    {
        $requisicao = new Requisicao('GET', cabecalhos: ['host' => 'escala.exemplo.com'], uri: '/');

        self::assertInstanceOf(RespostaJson::class, $this->middleware(conexaoSegura: false, forcar: false)->tratar($requisicao));
        self::assertInstanceOf(RespostaJson::class, $this->middleware(conexaoSegura: true, forcar: true)->tratar($requisicao));
    }

    #[Test]
    public function hostForjadoNaoViraDestinoDeRedirecionamento(): void
    {
        $resposta = $this->middleware(conexaoSegura: false, forcar: true)
            ->tratar(new Requisicao('GET', cabecalhos: ['host' => 'mal.com/@outro'], uri: '/'));

        self::assertInstanceOf(RespostaJson::class, $resposta);
    }

    private function middleware(bool $conexaoSegura, bool $forcar): ExigirHttps
    {
        $controller = new class () implements Manipulador {
            public function tratar(Requisicao $requisicao): Resposta
            {
                return RespostaJson::sucesso();
            }
        };
        return new ExigirHttps($controller, $conexaoSegura, $forcar);
    }
}
