<?php

declare(strict_types=1);

namespace Escala\Http\Middleware;

use Escala\Http\FormatoResposta;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\RespostaJson;
use Escala\Http\Seguranca\TokensCsrf;
use Escala\Http\View\RenderizadorDeTemplate;

/** Em POST, exige o token do formulário (campo csrf_token) ou do fetch (cabeçalho X-CSRF-Token). */
final class VerificarCsrf implements Manipulador
{
    private const MENSAGEM = 'Token de segurança inválido. Recarregue a página e tente novamente.';

    public function __construct(
        private readonly Manipulador $interno,
        private readonly TokensCsrf $tokens,
        private readonly FormatoResposta $formato,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        if (!$requisicao->ehPost()) {
            return $this->interno->tratar($requisicao);
        }
        $enviado = $requisicao->campo('csrf_token') ?? $requisicao->cabecalho('x-csrf-token');
        if ($this->tokens->valido($enviado)) {
            return $this->interno->tratar($requisicao);
        }
        return $this->formato === FormatoResposta::Json
            ? RespostaJson::erro(self::MENSAGEM, 403)
            : new RespostaHtml($this->templates->renderizar('erro', ['mensagem' => self::MENSAGEM]), 403);
    }
}
