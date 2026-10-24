<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Application;

use Escala\Application\Usuario\AlterarSituacaoUsuario;
use Escala\Application\Usuario\ResultadoSalvarUsuario;
use Escala\Application\Usuario\SalvarUsuario;
use Escala\Application\Usuario\SalvarUsuarioEntrada;
use Escala\Domain\Excecao\UltimoAdministrador;
use Escala\Domain\Excecao\UsuarioNaoEncontrado;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Domain\Usuario\ProtecaoUltimoAdministrador;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;
use Escala\Tests\Support\TransacaoImediata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UsuariosTest extends TestCase
{
    private UsuarioRepository&MockObject $usuarios;

    protected function setUp(): void
    {
        $this->usuarios = $this->createMock(UsuarioRepository::class);
    }

    /** @return iterable<string, array{Usuario, Usuario, int, bool}> */
    public static function cenariosDaProtecao(): iterable
    {
        $admin = self::usuario(PapelUsuario::Admin, true);
        yield 'desativar o último admin' => [$admin, $admin->comSituacao(false), 1, true];
        yield 'rebaixar o último admin' => [$admin, self::usuario(PapelUsuario::Bombeiro, true), 1, true];
        yield 'desativar um admin havendo outro' => [$admin, $admin->comSituacao(false), 2, false];
        yield 'manter o último admin como admin ativo' => [$admin, $admin, 1, false];
        yield 'desativar um bombeiro' => [self::usuario(PapelUsuario::Bombeiro, true), self::usuario(PapelUsuario::Bombeiro, false), 1, false];
        yield 'admin já inativo não conta' => [self::usuario(PapelUsuario::Admin, false), self::usuario(PapelUsuario::Bombeiro, false), 1, false];
    }

    #[Test]
    #[DataProvider('cenariosDaProtecao')]
    public function protecaoDoUltimoAdministrador(Usuario $antes, Usuario $depois, int $adminsAtivos, bool $deveBloquear): void
    {
        if ($deveBloquear) {
            $this->expectException(UltimoAdministrador::class);
        }

        (new ProtecaoUltimoAdministrador())->verificar($antes, $depois, $adminsAtivos);

        if (!$deveBloquear) {
            $this->addToAssertionCount(1);
        }
    }

    #[Test]
    public function desativarOUltimoAdminERecusadoSemGravar(): void
    {
        $this->usuarios->method('buscarPorId')->willReturn(self::usuario(PapelUsuario::Admin, true));
        $this->usuarios->expects(self::once())->method('contarAdminsAtivos')->with(true)->willReturn(1);
        $this->usuarios->expects(self::never())->method('atualizar');

        $this->expectException(UltimoAdministrador::class);

        $this->alterarSituacao()->executar(10, false);
    }

    #[Test]
    public function desativarComOutroAdminAtivoGravaNaTransacao(): void
    {
        $this->usuarios->method('buscarPorId')->willReturn(self::usuario(PapelUsuario::Admin, true));
        $this->usuarios->method('contarAdminsAtivos')->willReturn(2);
        $this->usuarios->expects(self::once())->method('atualizar')
            ->with(self::callback(static fn (Usuario $u): bool => $u->id === 10 && !$u->ativo));
        $transacao = new TransacaoImediata();

        $resultado = (new AlterarSituacaoUsuario($transacao, $this->usuarios, new ProtecaoUltimoAdministrador()))->executar(10, false);

        self::assertFalse($resultado->ativo);
        self::assertSame(1, $transacao->execucoes);
    }

    #[Test]
    public function reativarNuncaEBloqueado(): void
    {
        $this->usuarios->method('buscarPorId')->willReturn(self::usuario(PapelUsuario::Bombeiro, false));
        $this->usuarios->method('contarAdminsAtivos')->willReturn(0);
        $this->usuarios->expects(self::once())->method('atualizar');

        self::assertTrue($this->alterarSituacao()->executar(10, true)->ativo);
    }

    #[Test]
    public function usuarioInexistenteERecusado(): void
    {
        $this->usuarios->method('buscarPorId')->willReturn(null);

        $this->expectException(UsuarioNaoEncontrado::class);

        $this->alterarSituacao()->executar(99, false);
    }

    #[Test]
    public function substituirNaoPodeRebaixarOUltimoAdmin(): void
    {
        $this->usuarios->method('buscarPorLogin')->willReturn(self::usuario(PapelUsuario::Admin, true));
        $this->usuarios->method('contarAdminsAtivos')->willReturn(1);
        $this->usuarios->expects(self::never())->method('atualizar');

        $this->expectException(UltimoAdministrador::class);

        $this->salvarUsuario()->executar(new SalvarUsuarioEntrada('Ana', 'ana', 'senhaNova123', 'bc', true, true, null));
    }

    #[Test]
    public function criarUsuarioNovoNaoConsultaAContagemDeAdmins(): void
    {
        $this->usuarios->method('buscarPorLogin')->willReturn(null);
        $this->usuarios->expects(self::never())->method('contarAdminsAtivos');
        $this->usuarios->expects(self::once())->method('inserir')->willReturn(11);

        $resultado = $this->salvarUsuario()->executar(new SalvarUsuarioEntrada('Bia', 'bia', 'senhaNova123', 'bc', true, false, null));

        self::assertSame(ResultadoSalvarUsuario::Criado, $resultado);
    }

    private function alterarSituacao(): AlterarSituacaoUsuario
    {
        return new AlterarSituacaoUsuario(new TransacaoImediata(), $this->usuarios, new ProtecaoUltimoAdministrador());
    }

    private function salvarUsuario(): SalvarUsuario
    {
        return new SalvarUsuario(new TransacaoImediata(), $this->usuarios, new ProtecaoUltimoAdministrador());
    }

    private static function usuario(PapelUsuario $papel, bool $ativo): Usuario
    {
        return new Usuario(10, 'Ana', 'ana', 'hash', $papel, $ativo, null);
    }
}
