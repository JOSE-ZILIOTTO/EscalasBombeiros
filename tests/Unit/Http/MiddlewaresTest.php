<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Http;

use Escala\Application\Auth\Autenticador;
use Escala\Application\Auth\LimitadorDeTentativas;
use Escala\Application\Auth\Sessao;
use Escala\Domain\Excecao\PlantaoNaoEncontrado;
use Escala\Domain\Excecao\TurnoInvalido;
use Escala\Domain\Excecao\VagaIndisponivel;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;
use Escala\Http\FormatoResposta;
use Escala\Http\Manipulador;
use Escala\Http\Middleware\ExigirAutenticacao;
use Escala\Http\Middleware\TratarErros;
use Escala\Http\Middleware\VerificarCsrf;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;
use Escala\Http\Seguranca\TokensCsrf;
use Escala\Http\View\RenderizadorDeTemplate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class MiddlewaresTest extends TestCase
{
    #[Test]
    public function apiSemLoginRecebe401SemExecutarOController(): void
    {
        $resposta = $this->autenticacao(usuarioLogado: null, papel: PapelUsuario::Admin, formato: FormatoResposta::Json)
            ->tratar(new Requisicao('GET'));

        self::assertSame(401, $resposta->status());
    }

    #[Test]
    public function paginaDeAdminRedirecionaBombeiroParaOPortal(): void
    {
        $resposta = $this->autenticacao($this->usuario(PapelUsuario::Bombeiro), PapelUsuario::Admin, FormatoResposta::Html)
            ->tratar(new Requisicao('GET'));

        self::assertInstanceOf(Redirecionamento::class, $resposta);
        self::assertSame('portal_usuario.php', $resposta->destino);
    }

    #[Test]
    public function usuarioAutorizadoChegaAoControllerComoAtributo(): void
    {
        $resposta = $this->autenticacao($this->usuario(PapelUsuario::Admin), PapelUsuario::Admin, FormatoResposta::Json)
            ->tratar(new Requisicao('GET'));

        self::assertInstanceOf(RespostaJson::class, $resposta);
        self::assertSame('Ana', $resposta->dados['usuario']);
    }

    #[Test]
    public function postSemTokenCsrfERecusado(): void
    {
        $resposta = $this->csrf(tokenDaSessao: 'abc')->tratar(new Requisicao('POST'));

        self::assertSame(403, $resposta->status());
    }

    #[Test]
    public function postComTokenNoCabecalhoPassa(): void
    {
        $resposta = $this->csrf(tokenDaSessao: 'abc')->tratar(new Requisicao('POST', cabecalhos: ['x-csrf-token' => 'abc']));

        self::assertSame(200, $resposta->status());
    }

    #[Test]
    public function getNaoExigeTokenCsrf(): void
    {
        self::assertSame(200, $this->csrf(tokenDaSessao: null)->tratar(new Requisicao('GET'))->status());
    }

    #[Test]
    public function excecoesDeDominioViramStatusPelaHierarquia(): void
    {
        self::assertSame(409, $this->erros(VagaIndisponivel::paraBc())->status());
        self::assertSame(404, $this->erros(new PlantaoNaoEncontrado())->status());
        self::assertSame(400, $this->erros(new TurnoInvalido())->status());
    }

    #[Test]
    public function falhaTecnicaELogadaEOcultaDoUsuario(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $resposta = $this->erros(new RuntimeException('SQLSTATE[HY000] senha do banco...'), $logger);

        self::assertInstanceOf(RespostaJson::class, $resposta);
        self::assertSame(500, $resposta->status());
        self::assertStringNotContainsString('SQLSTATE', $resposta->dados['message']);
    }

    private function controllerQueEcoaUsuario(): Manipulador
    {
        return new class () implements Manipulador {
            public function tratar(Requisicao $requisicao): Resposta
            {
                $usuario = $requisicao->atributo(ExigirAutenticacao::ATRIBUTO_USUARIO);
                return new RespostaJson(['usuario' => $usuario instanceof Usuario ? $usuario->nome : null]);
            }
        };
    }

    private function autenticacao(?Usuario $usuarioLogado, ?PapelUsuario $papel, FormatoResposta $formato): ExigirAutenticacao
    {
        $sessao = $this->createStub(Sessao::class);
        $sessao->method('obter')->willReturn($usuarioLogado?->id);
        $usuarios = $this->createStub(UsuarioRepository::class);
        $usuarios->method('buscarPorId')->willReturn($usuarioLogado);
        $autenticador = new Autenticador($usuarios, $sessao, $this->createStub(LimitadorDeTentativas::class));

        return new ExigirAutenticacao($this->controllerQueEcoaUsuario(), $autenticador, $formato, $papel);
    }

    private function csrf(?string $tokenDaSessao): VerificarCsrf
    {
        $sessao = $this->createStub(Sessao::class);
        $sessao->method('obter')->willReturn($tokenDaSessao);
        $templates = $this->createStub(RenderizadorDeTemplate::class);
        $templates->method('renderizar')->willReturn('erro');

        return new VerificarCsrf($this->controllerQueEcoaUsuario(), new TokensCsrf($sessao), FormatoResposta::Json, $templates);
    }

    private function erros(Throwable $erro, ?LoggerInterface $logger = null): Resposta
    {
        $lanca = new class ($erro) implements Manipulador {
            public function __construct(private readonly Throwable $erro)
            {
            }

            public function tratar(Requisicao $requisicao): Resposta
            {
                throw $this->erro;
            }
        };
        $middleware = new TratarErros(
            $lanca,
            $logger ?? $this->createStub(LoggerInterface::class),
            FormatoResposta::Json,
            $this->createStub(RenderizadorDeTemplate::class),
        );
        return $middleware->tratar(new Requisicao('POST'));
    }

    private function usuario(PapelUsuario $papel): Usuario
    {
        return new Usuario(10, 'Ana', 'ana', 'hash', $papel, true, null);
    }
}
