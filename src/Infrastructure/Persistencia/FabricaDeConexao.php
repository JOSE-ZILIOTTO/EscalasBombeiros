<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Infrastructure\Config\Config;
use PDO;

final class FabricaDeConexao
{
    public function criar(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config->texto('DB_SERVER', 'localhost'),
            $config->texto('DB_NAME', 'escala_db'),
        );

        return new PDO($dsn, $config->textoObrigatorio('DB_USERNAME'), $config->texto('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // prepares reais: tipos chegam ao MySQL sem passar por interpolação do driver
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }
}
