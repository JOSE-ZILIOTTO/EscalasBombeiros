<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Config;

use RuntimeException;

final class ConfiguracaoAusente extends RuntimeException
{
    public function __construct(string $chave)
    {
        parent::__construct("Configuração obrigatória ausente: {$chave}. Crie o .env a partir do .env.example.");
    }
}
