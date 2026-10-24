<?php

declare(strict_types=1);

namespace Escala\Http\Middleware;

use Escala\Application\Auth\Autenticador;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Http\FormatoResposta;
use Escala\Http\Manipulador;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** Exige login (e opcionalmente um papel) e entrega o usuário ao controller como atributo. */
final class ExigirAutenticacao implements Manipulador
{
    public const ATRIBUTO_USUARIO = 'usuario';

    public function __construct(
        private readonly Manipulador $interno,
        private readonly Autenticador $autenticador,
        private readonly FormatoResposta $formato,
        private readonly ?PapelUsuario $papelExigido = null,
        private readonly string $paginaDeLogin = 'index.php',
        private readonly string $paginaSemPermissao = 'portal_usuario.php',
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $usuario = $this->autenticador->usuarioAtual();
        if ($usuario === null) {
            return $this->recusar('Não autenticado.', 401, $this->paginaDeLogin);
        }
        if ($this->papelExigido !== null && $usuario->papel !== $this->papelExigido) {
            return $this->recusar('Acesso restrito a administradores.', 403, $this->paginaSemPermissao);
        }
        return $this->interno->tratar($requisicao->comAtributo(self::ATRIBUTO_USUARIO, $usuario));
    }

    private function recusar(string $mensagem, int $status, string $destino): Resposta
    {
        return $this->formato === FormatoResposta::Json
            ? RespostaJson::erro($mensagem, $status)
            : new Redirecionamento($destino);
    }
}
