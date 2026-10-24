<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\OrdemEscolha\DefinirInicioOrdem;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** POST api/api_set_inicio_ordem.php */
final class DefinirInicioOrdemController implements Manipulador
{
    public function __construct(private readonly DefinirInicioOrdem $definirInicio)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $proximo = $this->definirInicio->executar($requisicao->campoInteiro('inicio_id'));
        return RespostaJson::sucesso('Início atualizado.', ['proximo_nome' => $proximo?->nomeCompleto ?? '(Nenhum)']);
    }
}
