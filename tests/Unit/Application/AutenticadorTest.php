<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Application;

use Escala\Application\Auth\Autenticador;
use Escala\Application\Auth\CredenciaisInvalidas;
use Escala\Application\Auth\LimitadorDeTentativas;
use Escala\Application\Auth\MuitasTentativasDeLogin;
use Escala\Application\Auth\Sessao;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AutenticadorTest extends TestCase
{
    private UsuarioRepository&MockObject $usuarios;
    private Sessao&MockObject $sessao;
    private LimitadorDeTentativas&MockObject $limitador;
    private Autenticador $autenticador;

    protected function setUp(): void
    {
        $this->usuarios = $this->createMock(UsuarioRepository::class);
        $this->sessao = $this->createMock(Sessao::class);
        $this->limitador = $this->createMock(LimitadorDeTentativas::class);
        $this->autenticador = new Autenticador($this->usuarios, $this->sessao, $this->limitador);
    }

    #[Test]
    public function loginCorretoRegeneraSessaoELimpaFalhas(): void
    {
        $this->usuarios->method('buscarPorLogin')->willReturn($this->usuario(senha: 'correta123'));
        $this->limitador->expects(self::once())->method('limpar')->with('1.2.3.4');
        $this->sessao->expects(self::once())->method('regenerarId');
        $this->sessao->expects(self::atLeastOnce())->method('definir');

        $usuario = $this->autenticador->entrar('ana', 'correta123', '1.2.3.4');

        self::assertSame(10, $usuario->id);
    }

    #[Test]
    public function senhaErradaRegistraFalhaENaoAbreSessao(): void
    {
        $this->usuarios->method('buscarPorLogin')->willReturn($this->usuario(senha: 'correta123'));
        $this->limitador->expects(self::once())->method('registrarFalha')->with('1.2.3.4');
        $this->sessao->expects(self::never())->method('regenerarId');

        $this->expectException(CredenciaisInvalidas::class);

        $this->autenticador->entrar('ana', 'errada', '1.2.3.4');
    }

    #[Test]
    public function usuarioInativoRecebeAMesmaMensagemDeSenhaErrada(): void
    {
        $this->usuarios->method('buscarPorLogin')->willReturn($this->usuario(senha: 'correta123', ativo: false));

        $this->expectExceptionObject(new CredenciaisInvalidas());

        $this->autenticador->entrar('ana', 'correta123', '1.2.3.4');
    }

    #[Test]
    public function clienteBloqueadoNemConsultaOBanco(): void
    {
        $this->limitador->method('estaBloqueado')->willReturn(true);
        $this->usuarios->expects(self::never())->method('buscarPorLogin');

        $this->expectException(MuitasTentativasDeLogin::class);

        $this->autenticador->entrar('ana', 'qualquer', '1.2.3.4');
    }

    #[Test]
    public function usuarioDesativadoDuranteASessaoPerdeOAcesso(): void
    {
        $this->sessao->method('obter')->with('usuario_id')->willReturn(10);
        $this->usuarios->method('buscarPorId')->willReturn($this->usuario(ativo: false));
        $this->sessao->expects(self::once())->method('encerrar');

        self::assertNull($this->autenticador->usuarioAtual());
    }

    #[Test]
    public function usuarioAtualEConsultadoUmaVezPorRequisicao(): void
    {
        $this->sessao->method('obter')->willReturn(10);
        $this->usuarios->expects(self::once())->method('buscarPorId')->willReturn($this->usuario());

        $this->autenticador->usuarioAtual();
        $this->autenticador->usuarioAtual();
    }

    private function usuario(string $senha = 'x', bool $ativo = true): Usuario
    {
        // custo mínimo do bcrypt para o teste não ficar lento
        $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 4]);
        return new Usuario(10, 'Ana', 'ana', $hash, PapelUsuario::Admin, $ativo, null);
    }
}
