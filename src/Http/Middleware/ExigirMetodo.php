<?php

declare(strict_types=1);

namespace Escala\Http\Middleware;

use Escala\Http\FormatoResposta;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\RespostaJson;

final class ExigirMetodo implements Manipulador
{
    /** @param list<string> $metodosPermitidos */
    public function __construct(
        private readonly Manipulador $interno,
        private readonly array $metodosPermitidos,
        private readonly FormatoResposta $formato,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        if (in_array($requisicao->metodo, $this->metodosPermitidos, true)) {
            return $this->interno->tratar($requisicao);
        }
        return $this->formato === FormatoResposta::Json
            ? RespostaJson::erro('Método não permitido.', 405)
            : new RespostaHtml('Método não permitido.', 405);
    }
}
