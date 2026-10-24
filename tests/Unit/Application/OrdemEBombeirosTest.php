<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Application;

use Escala\Application\Bombeiro\AtualizarBombeiro;
use Escala\Application\Bombeiro\AtualizarBombeiroEntrada;
use Escala\Application\Bombeiro\InativarBombeiro;
use Escala\Application\OrdemEscolha\AvancarOrdem;
use Escala\Application\OrdemEscolha\CarregadorDaOrdem;
use Escala\Application\OrdemEscolha\ConsultarOrdemDeEscolha;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\Cpf;
use Escala\Domain\Bombeiro\DadosCadastrais;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Excecao\CicloFixoInvalido;
use Escala\Domain\Excecao\CpfInvalido;
use Escala\Domain\OrdemEscolha\EstadoOrdemEscolha;
use Escala\Domain\OrdemEscolha\OrdemEscolhaRepository;
use Escala\Tests\Support\Objetos;
use Escala\Tests\Support\TransacaoImediata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class OrdemEBombeirosTest extends TestCase
{
    private BombeiroRepository&MockObject $bombeiros;
    private OrdemEscolhaRepository&MockObject $ordens;

    protected function setUp(): void
    {
        $this->bombeiros = $this->createMock(BombeiroRepository::class);
        $this->bombeiros->method('bcsAtivos')->willReturn([Objetos::bc(1, 'Ana'), Objetos::bc(2, 'Bia'), Objetos::bc(3, 'Caio')]);
        $this->ordens = $this->createMock(OrdemEscolhaRepository::class);
    }

    #[Test]
    public function consultarAOrdemNuncaGravaNoBanco(): void
    {
        $this->ordens->method('carregarEstado')->willReturn(new EstadoOrdemEscolha());
        $this->ordens->expects(self::never())->method('salvarEstado');

        $resumo = (new ConsultarOrdemDeEscolha($this->carregador()))->executar();

        self::assertSame('Ana', $resumo->sugerido?->nomeCompleto);
        self::assertSame(['Ana', 'Bia', 'Caio'], array_map(static fn (Bombeiro $b): string => $b->nomeCompleto, $resumo->sequencia));
    }

    #[Test]
    public function avancarGravaONovoEstadoEDevolveONovoDaVez(): void
    {
        $this->ordens->method('carregarEstado')->willReturn(new EstadoOrdemEscolha(ultimoQueIniciou: 3, daVez: 1));
        $this->ordens->expects(self::once())->method('salvarEstado')
            ->with(self::callback(static fn (EstadoOrdemEscolha $e): bool => $e->daVez === 2));

        $novo = (new AvancarOrdem(new TransacaoImediata(), $this->carregador()))->executar();

        self::assertSame('Bia', $novo?->nomeCompleto);
    }

    #[Test]
    public function inativarQuemEstaNaVezLiberaAVezNaMesmaTransacao(): void
    {
        $this->bombeiros->method('buscar')->willReturn(Objetos::bc(2));
        $this->bombeiros->expects(self::once())->method('atualizar')
            ->with(self::callback(static fn (Bombeiro $b): bool => $b->id === 2 && !$b->ativo));
        $this->ordens->method('carregarEstado')->willReturn(new EstadoOrdemEscolha(daVez: 2));
        $this->ordens->expects(self::once())->method('salvarEstado')
            ->with(self::callback(static fn (EstadoOrdemEscolha $e): bool => $e->daVez === null));
        $transacao = new TransacaoImediata();

        (new InativarBombeiro($transacao, $this->bombeiros, $this->carregador()))->executar(2);

        self::assertSame(1, $transacao->execucoes);
    }

    #[Test]
    public function atualizarMantemCpfAntigoSemRevalidar(): void
    {
        $cpfLegado = Cpf::doBanco('000.000.000-00'); // inválido, mas já estava gravado
        $this->bombeiros->method('buscar')->willReturn(new Bombeiro(4, 'Antigo', null, TipoBombeiro::Bc, true, null, new DadosCadastrais(cpf: $cpfLegado)));
        $this->bombeiros->expects(self::once())->method('atualizar')
            ->with(self::callback(static fn (Bombeiro $b): bool => $b->dados->cpf === $cpfLegado && $b->nomeGuerra === 'NOVO'));

        $this->atualizarBombeiro()->executar(new AtualizarBombeiroEntrada(4, 'Antigo', 'BC', true, nomeGuerra: ' NOVO ', cpf: '000.000.000-00'));
    }

    #[Test]
    public function atualizarValidaCpfQuandoMuda(): void
    {
        $this->bombeiros->method('buscar')->willReturn(Objetos::bc(4));
        $this->bombeiros->expects(self::never())->method('atualizar');

        $this->expectException(CpfInvalido::class);

        $this->atualizarBombeiro()->executar(new AtualizarBombeiroEntrada(4, 'Nome', 'BC', true, cpf: '123.456.789-00'));
    }

    #[Test]
    public function fixoSemReferenciaDeCicloNaoEGravado(): void
    {
        $this->bombeiros->method('buscar')->willReturn(Objetos::bc(4));
        $this->bombeiros->expects(self::never())->method('atualizar');

        $this->expectException(CicloFixoInvalido::class);

        $this->atualizarBombeiro()->executar(new AtualizarBombeiroEntrada(4, 'Nome', 'Fixo', true, fixoRefData: '2025-11-03', fixoRefDiaCiclo: ''));
    }

    private function carregador(): CarregadorDaOrdem
    {
        return new CarregadorDaOrdem($this->bombeiros, $this->ordens);
    }

    private function atualizarBombeiro(): AtualizarBombeiro
    {
        $ordens = $this->createStub(OrdemEscolhaRepository::class);
        $ordens->method('carregarEstado')->willReturn(new EstadoOrdemEscolha());
        return new AtualizarBombeiro(new TransacaoImediata(), $this->bombeiros, new CarregadorDaOrdem($this->bombeiros, $ordens));
    }
}
