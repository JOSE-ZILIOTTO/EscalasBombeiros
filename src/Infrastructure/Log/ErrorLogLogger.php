<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Log;

use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

/** PSR-3 sobre error_log(): em hospedagem compartilhada é o log que sempre existe. */
final class ErrorLogLogger extends AbstractLogger
{
    /** @param array<string, mixed> $context */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $substituicoes = [];
        foreach ($context as $chave => $valor) {
            if (is_scalar($valor) || $valor instanceof Stringable) {
                $substituicoes['{' . $chave . '}'] = (string) $valor;
            }
        }
        $linha = sprintf('[escala][%s] %s', strtoupper((string) $level), strtr((string) $message, $substituicoes));

        $erro = $context['exception'] ?? null;
        if ($erro instanceof Throwable) {
            $linha .= sprintf(' | %s: %s em %s:%d', $erro::class, $erro->getMessage(), $erro->getFile(), $erro->getLine());
        }
        error_log($linha);
    }
}
