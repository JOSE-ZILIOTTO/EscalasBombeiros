<?php

declare(strict_types=1);

namespace Escala\Http\Middleware;

use Escala\Http\Manipulador;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;

/**
 * Com FORCAR_HTTPS=1, qualquer acesso por http:// é mandado para https:// antes de
 * login, senha ou dado pessoal trafegarem sem criptografia.
 */
final class ExigirHttps implements Manipulador
{
    public function __construct(
        private readonly Manipulador $interno,
        private readonly bool $conexaoSegura,
        private readonly bool $forcar,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $host = $requisicao->cabecalho('host') ?? '';
        if (!$this->forcar || $this->conexaoSegura || preg_match('/^[A-Za-z0-9.-]+(:\d+)?$/', $host) !== 1) {
            return $this->interno->tratar($requisicao); // Host estranho: não redireciona para ele
        }
        return new Redirecionamento('https://' . $host . ($requisicao->uri !== '' ? $requisicao->uri : '/'));
    }
}
