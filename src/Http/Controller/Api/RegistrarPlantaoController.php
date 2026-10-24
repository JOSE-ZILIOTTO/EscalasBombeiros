<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\Escala\RegistrarPlantao;
use Escala\Application\Escala\RegistrarPlantaoEntrada;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** POST api/api_registrar_plantao.php */
final class RegistrarPlantaoController implements Manipulador
{
    public function __construct(private readonly RegistrarPlantao $registrarPlantao)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $this->registrarPlantao->executar(new RegistrarPlantaoEntrada(
            $requisicao->campoInteiro('bombeiro_id'),
            $requisicao->campo('data') ?? '',
            $requisicao->campo('turno') ?? '',
        ));
        return RespostaJson::sucesso('Plantão registrado com sucesso!');
    }
}
