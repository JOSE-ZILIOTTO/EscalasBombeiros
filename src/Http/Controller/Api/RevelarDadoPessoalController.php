<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\Bombeiro\ConsultarBombeiros;
use Escala\Domain\Excecao\CampoObrigatorio;
use Escala\Http\Controller\Pagina\EditarBombeiroController;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/**
 * POST api/api_revelar_dado.php — devolve um campo sigiloso (CPF ou dados bancários)
 * quando o administrador clica em "Mostrar". POST + CSRF: o valor nunca fica numa URL
 * nem no histórico do navegador.
 */
final class RevelarDadoPessoalController implements Manipulador
{
    public function __construct(private readonly ConsultarBombeiros $consultar)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $campo = $requisicao->campo('campo') ?? '';
        if (!in_array($campo, EditarBombeiroController::CAMPOS_SIGILOSOS, true)) {
            throw new CampoObrigatorio('Campo inválido.');
        }
        $dados = $this->consultar->buscar($requisicao->campoInteiro('bombeiro_id'))->dados;
        $valor = $campo === 'cpf' ? $dados->cpf?->formatado : $dados->dadosBancarios;

        return RespostaJson::sucesso(null, ['valor' => $valor ?? '']);
    }
}
