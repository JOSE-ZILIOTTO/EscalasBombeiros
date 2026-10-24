<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Domain\Usuario\TutorialRepository;
use Escala\Domain\Usuario\Usuario;
use Escala\Http\Manipulador;
use Escala\Http\Middleware\ExigirAutenticacao;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** POST api/api_concluir_tutorial.php — o admin concluiu ou pulou o tutorial: não abre sozinho de novo. */
final class ConcluirTutorialController implements Manipulador
{
    public function __construct(private readonly TutorialRepository $tutorial)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        /** @var Usuario $usuario */
        $usuario = $requisicao->atributo(ExigirAutenticacao::ATRIBUTO_USUARIO);
        $this->tutorial->marcarComoVisto((int) $usuario->id);
        return RespostaJson::sucesso();
    }
}
