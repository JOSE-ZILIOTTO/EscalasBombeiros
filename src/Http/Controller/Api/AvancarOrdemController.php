<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\OrdemEscolha\AvancarOrdem;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** POST api/api_avancar_ordem.php */
final class AvancarOrdemController implements Manipulador
{
    public function __construct(private readonly AvancarOrdem $avancarOrdem)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $novo = $this->avancarOrdem->executar();
        if ($novo === null) {
            return RespostaJson::sucesso('Não há mais bombeiros na ordem de escolha.', ['novo_nome' => '(Fim da lista)']);
        }
        return RespostaJson::sucesso('Ordem avançada com sucesso.', ['novo_nome' => $novo->nomeCompleto]);
    }
}
