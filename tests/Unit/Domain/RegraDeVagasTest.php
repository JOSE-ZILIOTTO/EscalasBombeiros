<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Domain;

use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\FixoDoDia;
use Escala\Domain\Escala\QuadroDoDia;
use Escala\Domain\Escala\RegraDeVagas;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Escala\Vagas;
use Escala\Domain\Excecao\SubstitutoNaoPermitido;
use Escala\Domain\Excecao\VagaIndisponivel;
use Escala\Tests\Support\Objetos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegraDeVagasTest extends TestCase
{
    private RegraDeVagas $regra;

    protected function setUp(): void
    {
        $this->regra = new RegraDeVagas();
    }

    #[Test]
    public function diaVazioComFixoValidoTemUmaVagaPorTurnoESemSubstituto(): void
    {
        $vagas = $this->regra->calcular([], fixoValido: true);

        self::assertSame([1, 1, 0], [$vagas->diurno, $vagas->noturno, $vagas->substituto]);
    }

    #[Test]
    public function semFixoValidoAbreVagaDeSubstituto(): void
    {
        self::assertSame(1, $this->regra->calcular([], fixoValido: false)->substituto);
    }

    #[Test]
    public function integralDeBcOcupaDiurnoENoturno(): void
    {
        $vagas = $this->regra->calcular([Objetos::plantao(Turno::Integral)], true);

        self::assertSame([0, 0], [$vagas->diurno, $vagas->noturno]);
    }

    #[Test]
    public function plantaoDeFixoNaoOcupaVagaDeBc(): void
    {
        $vagas = $this->regra->calcular([
            Objetos::plantao(Turno::Diurno, TipoBombeiro::Fixo),
            Objetos::plantao(Turno::Noturno, TipoBombeiro::Fixo),
        ], true);

        self::assertSame([1, 1], [$vagas->diurno, $vagas->noturno]);
    }

    #[Test]
    public function substitutoOcupadoPorQualquerTipo(): void
    {
        $vagas = $this->regra->calcular([Objetos::plantao(Turno::IntegralSubstituto, TipoBombeiro::Fixo)], false);

        self::assertSame(0, $vagas->substituto);
    }

    #[Test]
    public function vagasNuncaFicamNegativasComDadosInconsistentes(): void
    {
        $vagas = $this->regra->calcular([
            Objetos::plantao(Turno::Integral),
            Objetos::plantao(Turno::Diurno, bombeiroId: 2),
            Objetos::plantao(Turno::IntegralSubstituto, bombeiroId: 3),
            Objetos::plantao(Turno::IntegralSubstituto, bombeiroId: 4),
        ], false);

        self::assertSame([0, 0, 0], [$vagas->diurno, $vagas->noturno, $vagas->substituto]);
    }

    #[Test]
    public function quantidadeDeVagasPorTurnoEConfiguravel(): void
    {
        $vagas = (new RegraDeVagas(vagasBcPorTurno: 2))->calcular([Objetos::plantao(Turno::Diurno)], true);

        self::assertSame(1, $vagas->diurno);
    }

    /** @return iterable<string, array{Turno, Vagas}> */
    public static function turnosSemVaga(): iterable
    {
        yield 'diurno lotado' => [Turno::Diurno, new Vagas(0, 1, 0)];
        yield 'noturno lotado' => [Turno::Noturno, new Vagas(1, 0, 0)];
        yield 'integral precisa dos dois' => [Turno::Integral, new Vagas(1, 0, 0)];
    }

    #[Test]
    #[DataProvider('turnosSemVaga')]
    public function bcNaoPodeOcuparTurnoLotado(Turno $turno, Vagas $vagas): void
    {
        $this->expectException(VagaIndisponivel::class);

        $this->regra->garantirDisponibilidade($turno, TipoBombeiro::Bc, $this->quadro($vagas, fixoValido: true));
    }

    #[Test]
    public function fixoPodeFazerTrocaMesmoComTurnoLotado(): void
    {
        $this->regra->garantirDisponibilidade(Turno::Diurno, TipoBombeiro::Fixo, $this->quadro(new Vagas(0, 0, 0), true));

        $this->addToAssertionCount(1); // não lançou exceção
    }

    #[Test]
    public function substitutoSoExisteSemFixoValido(): void
    {
        $this->expectException(SubstitutoNaoPermitido::class);

        $this->regra->garantirDisponibilidade(Turno::IntegralSubstituto, TipoBombeiro::Bc, $this->quadro(new Vagas(1, 1, 0), fixoValido: true));
    }

    #[Test]
    public function substitutoJaOcupadoERecusado(): void
    {
        $this->expectExceptionObject(VagaIndisponivel::paraSubstituto());

        $this->regra->garantirDisponibilidade(Turno::IntegralSubstituto, TipoBombeiro::Bc, $this->quadro(new Vagas(1, 1, 0), fixoValido: false));
    }

    private function quadro(Vagas $vagas, bool $fixoValido): QuadroDoDia
    {
        $fixo = $fixoValido ? new FixoDoDia(Objetos::fixo(9, '2025-11-03'), false) : null;
        return new QuadroDoDia(DataEscala::deTexto('2025-11-03'), $fixo, [], $vagas);
    }
}
