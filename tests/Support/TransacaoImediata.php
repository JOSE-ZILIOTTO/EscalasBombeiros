<?php

declare(strict_types=1);

namespace Escala\Tests\Support;

use Escala\Application\Transacao;

/** Dublê de Transacao: executa direto e conta as chamadas, para provar que o caso de uso usa transação. */
final class TransacaoImediata implements Transacao
{
    public int $execucoes = 0;

    public function executar(callable $operacao): mixed
    {
        $this->execucoes++;
        return $operacao();
    }
}
