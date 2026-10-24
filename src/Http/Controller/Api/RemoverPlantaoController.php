<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\Escala\RemoverPlantao;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** POST api/api_remover_plantao.php */
final class RemoverPlantaoController implements Manipulador
{
    public function __construct(private readonly RemoverPlantao $removerPlantao)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $this->removerPlantao->executar($requisicao->campoInteiro('plantao_id'));
        return RespostaJson::sucesso('Removido!');
    }
}
