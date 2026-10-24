<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\Escala\RemoverExcecaoFixo;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** POST api/api_remover_excecao_fixo.php */
final class RemoverExcecaoFixoController implements Manipulador
{
    public function __construct(private readonly RemoverExcecaoFixo $removerExcecao)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $this->removerExcecao->executar($requisicao->campoInteiro('bombeiro_id'), $requisicao->campo('data') ?? '');
        return RespostaJson::sucesso('Exceção removida (Ciclo restaurado).');
    }
}
