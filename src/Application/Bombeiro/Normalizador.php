<?php

declare(strict_types=1);

namespace Escala\Application\Bombeiro;

/** Regras de limpeza de texto de formulário compartilhadas pelos casos de uso de cadastro. */
final class Normalizador
{
    public const TAMANHO_NOME_GUERRA = 60;

    /** Texto aparado; vazio vira null (o banco grava NULL para campos opcionais em branco). */
    public static function opcional(?string $valor, ?int $tamanhoMaximo = null): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        return $tamanhoMaximo === null ? $valor : mb_substr($valor, 0, $tamanhoMaximo);
    }
}
