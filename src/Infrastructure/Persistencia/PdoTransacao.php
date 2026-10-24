<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Application\Transacao;
use PDO;
use Throwable;

final class PdoTransacao implements Transacao
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function executar(callable $operacao): mixed
    {
        // um caso de uso chamado dentro de outro reaproveita a transação já aberta
        if ($this->pdo->inTransaction()) {
            return $operacao();
        }

        $this->pdo->beginTransaction();
        try {
            $resultado = $operacao();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $erro) {
            $this->pdo->rollBack();
            throw $erro;
        }
    }
}
