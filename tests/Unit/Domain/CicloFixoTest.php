<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Domain;

use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\EscalaDoFixo;
use Escala\Domain\Escala\ExcecoesDoPeriodo;
use Escala\Domain\Escala\ReferenciaCicloFixo;
use Escala\Domain\Excecao\CicloFixoInvalido;
use Escala\Tests\Support\Objetos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CicloFixoTest extends TestCase
{
    /** @return iterable<string, array{string, int}> */
    public static function datas(): iterable
    {
        yield 'na referência' => ['2025-04-03', 1];
        yield 'dia seguinte' => ['2025-04-04', 2];
        yield 'terceiro dia de folga' => ['2025-04-06', 4];
        yield 'novo ciclo' => ['2025-04-07', 1];
        yield 'antes da referência' => ['2025-04-02', 4];
        yield 'muito antes (resto negativo)' => ['2025-03-30', 1];
        yield 'atravessa ano' => ['2026-01-01', 1 + ((273 % 4) + 4) % 4];
    }

    #[Test]
    #[DataProvider('datas')]
    public function calculaDiaDoCiclo(string $data, int $esperado): void
    {
        $referencia = new ReferenciaCicloFixo(DataEscala::deTexto('2025-04-03'), 1);

        self::assertSame($esperado, (new CicloFixo())->diaDoCiclo($referencia, DataEscala::deTexto($data)));
    }

    #[Test]
    public function referenciaComDiaForaDoCicloERecusada(): void
    {
        $this->expectException(CicloFixoInvalido::class);

        new ReferenciaCicloFixo(DataEscala::deTexto('2025-04-03'), 5);
    }

    #[Test]
    public function escolheOPrimeiroFixoDeServicoSemExcecao(): void
    {
        $data = DataEscala::deTexto('2025-11-03');
        $comExcecao = Objetos::fixo(1, '2025-11-03', 'Com Exceção');
        $valido = Objetos::fixo(2, '2025-11-03', 'Válido');

        $fixo = (new EscalaDoFixo(new CicloFixo()))->fixoDoDia(
            $data,
            [$comExcecao, $valido],
            new ExcecoesDoPeriodo([['bombeiroId' => 1, 'data' => $data]]),
        );

        self::assertSame('Válido', $fixo?->bombeiro->nomeCompleto);
        self::assertTrue($fixo->ehValido());
    }

    #[Test]
    public function devolveFixoComExcecaoMarcadoParaPermitirRestaurar(): void
    {
        $data = DataEscala::deTexto('2025-11-03');

        $fixo = (new EscalaDoFixo(new CicloFixo()))->fixoDoDia(
            $data,
            [Objetos::fixo(1, '2025-11-03')],
            new ExcecoesDoPeriodo([['bombeiroId' => 1, 'data' => $data]]),
        );

        self::assertNotNull($fixo);
        self::assertTrue($fixo->temExcecao);
    }

    #[Test]
    public function diaSemFixoDeServicoDevolveNull(): void
    {
        $fixo = (new EscalaDoFixo(new CicloFixo()))->fixoDoDia(
            DataEscala::deTexto('2025-11-04'),
            [Objetos::fixo(1, '2025-11-03')],
            new ExcecoesDoPeriodo(),
        );

        self::assertNull($fixo);
    }
}
