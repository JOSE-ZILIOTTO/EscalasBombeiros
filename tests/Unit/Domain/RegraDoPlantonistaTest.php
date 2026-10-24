<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Domain;

use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\RegraDoPlantonista;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Excecao\BombeiroInativo;
use Escala\Domain\Excecao\FixoEmDiaDeServico;
use Escala\Domain\Excecao\TurnoSobreposto;
use Escala\Tests\Support\Objetos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegraDoPlantonistaTest extends TestCase
{
    private RegraDoPlantonista $regra;

    protected function setUp(): void
    {
        $this->regra = new RegraDoPlantonista(new CicloFixo());
    }

    #[Test]
    public function bombeiroInativoNaoRecebePlantao(): void
    {
        $this->expectException(BombeiroInativo::class);
        $this->regra->garantir(Objetos::bc(5, ativo: false), DataEscala::deTexto('2025-11-03'), Turno::Diurno, []);
    }

    /** @return iterable<string, array{Turno}> */
    public static function todosOsTurnos(): iterable
    {
        foreach (Turno::cases() as $turno) {
            yield $turno->name => [$turno];
        }
    }

    #[Test]
    #[DataProvider('todosOsTurnos')]
    public function fixoNaoRecebeNadaNoProprioDiaDeCiclo(Turno $turno): void
    {
        // serviço em 01/11, 05/11, 09/11...; com ou sem ausência marcada, o dia é dele
        $fixo = Objetos::fixo(9, '2025-11-01');

        $this->expectException(FixoEmDiaDeServico::class);
        $this->regra->garantir($fixo, DataEscala::deTexto('2025-11-05'), $turno, []);
    }

    #[Test]
    public function fixoPodeCobrirEmDiaDeFolga(): void
    {
        $fixo = Objetos::fixo(9, '2025-11-01');

        $this->regra->garantir($fixo, DataEscala::deTexto('2025-11-06'), Turno::IntegralSubstituto, []);
        $this->regra->garantir($fixo, DataEscala::deTexto('2025-11-07'), Turno::Noturno, []);
        $this->addToAssertionCount(2);
    }

    /** @return iterable<string, array{Turno, Turno}> */
    public static function sobrepostos(): iterable
    {
        yield 'I depois de N' => [Turno::Noturno, Turno::Integral];
        yield 'N depois de I' => [Turno::Integral, Turno::Noturno];
        yield 'D depois de I_SUB' => [Turno::IntegralSubstituto, Turno::Diurno];
        yield 'I_SUB depois de I' => [Turno::Integral, Turno::IntegralSubstituto];
        yield 'D repetido' => [Turno::Diurno, Turno::Diurno];
    }

    #[Test]
    #[DataProvider('sobrepostos')]
    public function mesmaPessoaNaoTemTurnosComHorarioEmComum(Turno $existente, Turno $novo): void
    {
        $data = DataEscala::deTexto('2025-11-07');
        $plantoes = [Objetos::plantao($existente, TipoBombeiro::Fixo, bombeiroId: 9, data: '2025-11-07')];

        $this->expectException(TurnoSobreposto::class);
        $this->regra->garantir(Objetos::fixo(9, '2025-11-01'), $data, $novo, $plantoes);
    }

    #[Test]
    public function diurnoENoturnoDaMesmaPessoaNaoSeCruzamEColegaNaoConta(): void
    {
        $data = DataEscala::deTexto('2025-11-03');
        $plantoes = [
            Objetos::plantao(Turno::Diurno, bombeiroId: 5, data: '2025-11-03'),
            Objetos::plantao(Turno::Integral, bombeiroId: 6, data: '2025-11-03'),
        ];

        $this->regra->garantir(Objetos::bc(5), $data, Turno::Noturno, $plantoes);
        $this->addToAssertionCount(1);
    }
}
