<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Application;

use Escala\Application\Escala\RegistrarPlantao;
use Escala\Application\Escala\RegistrarPlantaoEntrada;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\EscalaDoFixo;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\ExcecoesDoPeriodo;
use Escala\Domain\Escala\MontadorDeQuadro;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Escala\RegraDeVagas;
use Escala\Domain\Escala\RegraDoPlantonista;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Excecao\BombeiroNaoEncontrado;
use Escala\Domain\Excecao\CampoObrigatorio;
use Escala\Domain\Excecao\DataInvalida;
use Escala\Domain\Excecao\FixoEmDiaDeServico;
use Escala\Domain\Excecao\TurnoInvalido;
use Escala\Domain\Excecao\VagaIndisponivel;
use Escala\Tests\Support\Objetos;
use Escala\Tests\Support\TransacaoImediata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class RegistrarPlantaoTest extends TestCase
{
    private BombeiroRepository&Stub $bombeiros;
    private PlantaoRepository&MockObject $plantoes;
    private TransacaoImediata $transacao;
    private RegistrarPlantao $casoDeUso;

    protected function setUp(): void
    {
        $this->bombeiros = $this->createStub(BombeiroRepository::class);
        $this->bombeiros->method('fixosAtivos')->willReturn([]);
        $this->plantoes = $this->createMock(PlantaoRepository::class);
        $excecoes = $this->createStub(ExcecaoFixoRepository::class);
        $excecoes->method('doPeriodo')->willReturn(new ExcecoesDoPeriodo());
        $this->transacao = new TransacaoImediata();

        // regras de domínio reais: são puras, então o teste exercita a regra de verdade
        $regra = new RegraDeVagas();
        $this->casoDeUso = new RegistrarPlantao(
            $this->transacao,
            $this->bombeiros,
            $this->plantoes,
            $excecoes,
            new MontadorDeQuadro(new EscalaDoFixo(new CicloFixo()), $regra),
            $regra,
            new RegraDoPlantonista(new CicloFixo()),
        );
    }

    #[Test]
    public function registraComVagaLivreDentroDeTransacaoETravandoODia(): void
    {
        $this->bombeiros->method('buscar')->willReturn(Objetos::bc(5));
        $this->plantoes->expects(self::once())->method('doDia')
            ->with(self::callback(static fn (DataEscala $d): bool => $d->emTexto() === '2025-11-03'), true)
            ->willReturn([]);
        $this->plantoes->expects(self::once())->method('inserir')
            ->with(5, self::isInstanceOf(DataEscala::class), Turno::Diurno)
            ->willReturn(42);

        $id = $this->casoDeUso->executar(new RegistrarPlantaoEntrada(5, '2025-11-03', 'D'));

        self::assertSame(42, $id);
        self::assertSame(1, $this->transacao->execucoes);
    }

    #[Test]
    public function naoInsereFixoNoProprioDiaDeCiclo(): void
    {
        $this->bombeiros->method('buscar')->willReturn(Objetos::fixo(9, '2025-11-01'));
        $this->plantoes->method('doDia')->willReturn([]);
        $this->plantoes->expects(self::never())->method('inserir');

        $this->expectException(FixoEmDiaDeServico::class);
        $this->casoDeUso->executar(new RegistrarPlantaoEntrada(9, '2025-11-05', 'I_SUB'));
    }

    #[Test]
    public function naoInsereQuandoATurnoEstaLotado(): void
    {
        $this->bombeiros->method('buscar')->willReturn(Objetos::bc(5));
        $this->plantoes->method('doDia')->willReturn([Objetos::plantao(Turno::Diurno, bombeiroId: 7)]);
        $this->plantoes->expects(self::never())->method('inserir');

        $this->expectException(VagaIndisponivel::class);

        $this->casoDeUso->executar(new RegistrarPlantaoEntrada(5, '2025-11-03', 'D'));
    }

    #[Test]
    public function bombeiroInexistenteERecusado(): void
    {
        $this->bombeiros->method('buscar')->willReturn(null);
        $this->plantoes->expects(self::never())->method('inserir');

        $this->expectException(BombeiroNaoEncontrado::class);

        $this->casoDeUso->executar(new RegistrarPlantaoEntrada(5, '2025-11-03', 'D'));
    }

    #[Test]
    public function entradaIncompletaNemAbreTransacao(): void
    {
        try {
            $this->casoDeUso->executar(new RegistrarPlantaoEntrada(0, '2025-11-03', 'D'));
            self::fail('Deveria recusar bombeiro 0.');
        } catch (CampoObrigatorio) {
            self::assertSame(0, $this->transacao->execucoes);
        }
    }

    #[Test]
    public function dataETurnoSaoValidadosAntesDoBanco(): void
    {
        $this->plantoes->expects(self::never())->method('doDia');

        try {
            $this->casoDeUso->executar(new RegistrarPlantaoEntrada(5, '2025-02-30', 'D'));
            self::fail('Data inválida deveria ser recusada.');
        } catch (DataInvalida) {
        }

        $this->expectException(TurnoInvalido::class);
        $this->casoDeUso->executar(new RegistrarPlantaoEntrada(5, '2025-11-03', 'X'));
    }
}
