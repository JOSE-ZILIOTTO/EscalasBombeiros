<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\Escala\RegistrarExcecaoFixo;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** POST api/api_registrar_excecao_fixo.php */
final class RegistrarExcecaoFixoController implements Manipulador
{
    public function __construct(private readonly RegistrarExcecaoFixo $registrarExcecao)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $this->registrarExcecao->executar($requisicao->campoInteiro('bombeiro_id'), $requisicao->campo('data') ?? '');
        return RespostaJson::sucesso('Exceção registrada com sucesso.');
    }
}
