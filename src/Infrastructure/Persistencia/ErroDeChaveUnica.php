<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use PDOException;

/** Identifica violação de UNIQUE do MySQL (erro 1062), opcionalmente de um índice específico. */
final class ErroDeChaveUnica
{
    private const CODIGO_MYSQL_DUPLICADO = 1062;

    public static function ehViolacao(PDOException $erro, ?string $indice = null): bool
    {
        $codigo = (int) ($erro->errorInfo[1] ?? 0);
        if ($codigo !== self::CODIGO_MYSQL_DUPLICADO) {
            return false;
        }
        return $indice === null || str_contains($erro->getMessage(), $indice);
    }
}
