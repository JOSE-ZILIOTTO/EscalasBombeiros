<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Domain;

use DateTimeImmutable;
use Escala\Domain\Bombeiro\Cpf;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Escala\TabelaDeRemuneracao;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Excecao\CpfInvalido;
use Escala\Domain\Excecao\DataInvalida;
use Escala\Domain\Excecao\TurnoInvalido;
use Escala\Domain\Shared\Dinheiro;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function datasInvalidas(): iterable
    {
        yield 'dia inexistente' => ['2025-02-30'];
        yield 'formato brasileiro' => ['03/11/2025'];
        yield 'texto' => ['amanhã'];
        yield 'vazio' => [''];
    }

    #[Test]
    #[DataProvider('datasInvalidas')]
    public function dataEscalaRecusaDatasInvalidas(string $texto): void
    {
        $this->expectException(DataInvalida::class);

        DataEscala::deTexto($texto);
    }

    #[Test]
    public function dataEscalaSabeFimDeSemanaEDiferenca(): void
    {
        $sabado = DataEscala::deTexto('2025-11-01');

        self::assertTrue($sabado->ehFimDeSemana());
        self::assertSame(2, $sabado->diasAte(DataEscala::deTexto('2025-11-03')));
        self::assertSame(-1, $sabado->diasAte(DataEscala::deTexto('2025-10-31')));
    }

    #[Test]
    public function mesEscalaCaiNoMesAtualCampoACampoQuandoParametroEInvalido(): void
    {
        $hoje = new DateTimeImmutable('2026-10-24');

        $mes = MesEscala::deParametros('2025', '13', $hoje);

        self::assertSame([2025, 10], [$mes->ano, $mes->mes]);
    }

    #[Test]
    public function mesEscalaNavegaEntreAnos(): void
    {
        $janeiro = MesEscala::de(2026, 1);

        self::assertSame([2025, 12], [$janeiro->anterior()->ano, $janeiro->anterior()->mes]);
        self::assertSame(28, MesEscala::de(2026, 2)->quantidadeDeDias());
        self::assertSame(29, MesEscala::de(2028, 2)->quantidadeDeDias());
        self::assertSame('Março', MesEscala::de(2026, 3)->nome());
    }

    #[Test]
    public function turnoConheceHorasEOcupacao(): void
    {
        self::assertSame(24, Turno::IntegralSubstituto->horas());
        self::assertTrue(Turno::Integral->ocupaDiurno() && Turno::Integral->ocupaNoturno());
        self::assertFalse(Turno::IntegralSubstituto->ocupaDiurno());
        self::assertSame('I', Turno::IntegralSubstituto->codigoPlanilha());
    }

    #[Test]
    public function turnoDesconhecidoERecusado(): void
    {
        $this->expectException(TurnoInvalido::class);

        Turno::deCodigo('X');
    }

    #[Test]
    public function cpfNovoEValidadoEFormatado(): void
    {
        self::assertSame('529.982.247-25', Cpf::criar('52998224725')->formatado);
    }

    /** @return iterable<string, array{string}> */
    public static function cpfsInvalidos(): iterable
    {
        yield 'dígito errado' => ['529.982.247-26'];
        yield 'repetido' => ['111.111.111-11'];
        yield 'curto' => ['123'];
    }

    #[Test]
    #[DataProvider('cpfsInvalidos')]
    public function cpfNovoInvalidoERecusado(string $valor): void
    {
        $this->expectException(CpfInvalido::class);

        Cpf::criar($valor);
    }

    #[Test]
    public function cpfDoBancoEAceitoSemValidacao(): void
    {
        self::assertSame('123', Cpf::doBanco('123')->formatado);
    }

    #[Test]
    public function dinheiroSomaEmCentavosEMantemInteiroNoJson(): void
    {
        $total = Dinheiro::reais(125)->somar(Dinheiro::centavos(12550));

        self::assertSame(25050, $total->centavos);
        self::assertSame(250.5, $total->emReais());
        self::assertSame(250, Dinheiro::reais(250)->emReais());
    }

    #[Test]
    public function tabelaDeRemuneracaoUsaADuracaoDoTurno(): void
    {
        $tabela = new TabelaDeRemuneracao(Dinheiro::reais(125), Dinheiro::reais(250));

        self::assertSame(125, $tabela->valorDe(Turno::Noturno)->emReais());
        self::assertSame(250, $tabela->valorDe(Turno::IntegralSubstituto)->emReais());
    }
}
