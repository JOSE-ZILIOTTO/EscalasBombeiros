<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Application;

use DateTimeImmutable;
use Escala\Application\Bombeiro\AnonimizarBombeiros;
use Escala\Application\Bombeiro\AtualizarBombeiro;
use Escala\Application\Bombeiro\AtualizarBombeiroEntrada;
use Escala\Application\OrdemEscolha\CarregadorDaOrdem;
use Escala\Application\Portal\MeusDados;
use Escala\Application\Relogio;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\Cpf;
use Escala\Domain\Bombeiro\DadosCadastrais;
use Escala\Domain\Bombeiro\PoliticaDeRetencao;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\OrdemEscolha\EstadoOrdemEscolha;
use Escala\Domain\OrdemEscolha\OrdemEscolhaRepository;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Domain\Usuario\Usuario;
use Escala\Tests\Support\TransacaoImediata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DadosPessoaisTest extends TestCase
{
    private BombeiroRepository&MockObject $bombeiros;

    protected function setUp(): void
    {
        $this->bombeiros = $this->createMock(BombeiroRepository::class);
    }

    #[Test]
    public function edicaoSemAbrirOsCamposSigilososMantemCpfEBanco(): void
    {
        $this->bombeiros->method('buscar')->willReturn($this->comDados(ativo: true));
        $this->bombeiros->expects(self::once())->method('atualizar')->with(self::callback(
            static fn (Bombeiro $b): bool => $b->dados->cpf?->formatado === '123.456.789-09'
                && $b->dados->dadosBancarios === 'Banco Exemplo'
                && $b->dados->telefonePrincipal === '(11) 1234-5678',
        ));

        // cpf e dadosBancarios null: os campos não foram abertos na tela, então não vieram no POST
        $this->atualizar()->executar(new AtualizarBombeiroEntrada(7, 'Fulano', 'BC', true, telefonePrincipal: '(11) 1234-5678'));
    }

    #[Test]
    public function campoSigilosoAbertoEEnviadoEmBrancoApagaOValor(): void
    {
        $this->bombeiros->method('buscar')->willReturn($this->comDados(ativo: true));
        $this->bombeiros->expects(self::once())->method('atualizar')->with(self::callback(
            static fn (Bombeiro $b): bool => $b->dados->cpf === null && $b->dados->dadosBancarios === null,
        ));

        $this->atualizar()->executar(new AtualizarBombeiroEntrada(7, 'Fulano', 'BC', true, cpf: '', dadosBancarios: ' '));
    }

    #[Test]
    public function edicaoPreservaInativacaoEAnonimizacao(): void
    {
        $atual = new Bombeiro(7, 'Fulano', null, TipoBombeiro::Bc, false, null, new DadosCadastrais(), DataEscala::deTexto('2024-01-01'), true);
        $this->bombeiros->method('buscar')->willReturn($atual);
        $this->bombeiros->expects(self::once())->method('atualizar')->with(self::callback(
            static fn (Bombeiro $b): bool => $b->anonimizado && $b->inativadoEm?->emTexto() === '2024-01-01',
        ));

        $this->atualizar()->executar(new AtualizarBombeiroEntrada(7, 'Fulano', 'BC', false));
    }

    #[Test]
    public function apagaSoOsInativosComPrazoVencido(): void
    {
        $vencido = $this->comDados(ativo: false, id: 1, inativadoEm: '2019-06-01');
        $noPrazo = $this->comDados(ativo: false, id: 2, inativadoEm: '2023-06-01');
        $ativo = $this->comDados(ativo: true, id: 3);
        $this->bombeiros->method('todos')->willReturn([$vencido, $noPrazo, $ativo]);
        $this->bombeiros->expects(self::once())->method('atualizar')->with(self::callback(
            static fn (Bombeiro $b): bool => $b->id === 1 && $b->anonimizado && !$b->temDadosPessoais(),
        ));
        $transacao = new TransacaoImediata();

        $total = (new AnonimizarBombeiros($transacao, $this->bombeiros, new PoliticaDeRetencao(5), $this->relogio('2025-01-01')))->executarVencidos();

        self::assertSame(1, $total);
        self::assertSame(1, $transacao->execucoes);
    }

    #[Test]
    public function copiaDosMeusDadosTemOCadastroCompletoMasNaoASenha(): void
    {
        $usuario = new Usuario(1, 'Fulano', 'fulano', '$2y$hash-secreto', PapelUsuario::Bombeiro, true, 7);

        $copia = (new MeusDados($usuario, $this->comDados(ativo: true), []))->exportacao('2025-01-01T00:00:00-03:00');

        self::assertSame('123.456.789-09', $copia['cadastro']['cpf']);
        self::assertSame('Banco Exemplo', $copia['cadastro']['dados_bancarios']);
        self::assertStringNotContainsString('hash-secreto', json_encode($copia, JSON_THROW_ON_ERROR));
    }

    private function atualizar(): AtualizarBombeiro
    {
        $ordens = $this->createStub(OrdemEscolhaRepository::class);
        $ordens->method('carregarEstado')->willReturn(new EstadoOrdemEscolha());
        $this->bombeiros->method('bcsAtivos')->willReturn([]);
        return new AtualizarBombeiro(new TransacaoImediata(), $this->bombeiros, new CarregadorDaOrdem($this->bombeiros, $ordens));
    }

    private function comDados(bool $ativo, int $id = 7, ?string $inativadoEm = null): Bombeiro
    {
        return new Bombeiro(
            $id,
            'Fulano',
            null,
            TipoBombeiro::Bc,
            $ativo,
            null,
            new DadosCadastrais(cpf: Cpf::criar('123.456.789-09'), dadosBancarios: 'Banco Exemplo'),
            $inativadoEm === null ? null : DataEscala::deTexto($inativadoEm),
        );
    }

    private function relogio(string $hoje): Relogio
    {
        return new class ($hoje) implements Relogio {
            public function __construct(private readonly string $hoje)
            {
            }

            public function agora(): DateTimeImmutable
            {
                return new DateTimeImmutable($this->hoje);
            }
        };
    }
}
