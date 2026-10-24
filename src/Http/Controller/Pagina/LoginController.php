<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Auth\Autenticador;
use Escala\Domain\Excecao\ExcecaoDeDominio;
use Escala\Domain\Usuario\Usuario;
use Escala\Http\Manipulador;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\Seguranca\TokensCsrf;
use Escala\Http\View\RenderizadorDeTemplate;

/**
 * index.php. Verifica o CSRF aqui, e não pelo middleware, porque um token expirado
 * no login deve reexibir o formulário com aviso em vez de uma página de erro.
 */
final class LoginController implements Manipulador
{
    public function __construct(
        private readonly Autenticador $autenticador,
        private readonly TokensCsrf $csrf,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        if (!$requisicao->ehPost()) {
            $logado = $this->autenticador->usuarioAtual();
            return $logado !== null ? $this->destinoDe($logado) : $this->formulario('');
        }

        if (!$this->csrf->valido($requisicao->campo('csrf_token'))) {
            return $this->formulario('Sessão expirada. Tente novamente.');
        }
        try {
            $usuario = $this->autenticador->entrar(
                $requisicao->campo('user') ?? '',
                $requisicao->campo('pass') ?? '',
                $requisicao->ip,
            );
        } catch (ExcecaoDeDominio $erro) {
            return $this->formulario($erro->getMessage());
        }
        $this->csrf->renovar();
        return $this->destinoDe($usuario);
    }

    private function destinoDe(Usuario $usuario): Redirecionamento
    {
        return new Redirecionamento($usuario->ehAdmin() ? 'escala.php' : 'portal_usuario.php');
    }

    private function formulario(string $erro): RespostaHtml
    {
        return new RespostaHtml($this->templates->renderizar('login', ['erro' => $erro]));
    }
}
