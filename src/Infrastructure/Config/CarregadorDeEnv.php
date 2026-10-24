<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Config;

use RuntimeException;

final class CarregadorDeEnv
{
    /** @param array<string, string> $ambiente normalmente getenv() */
    public function carregar(string $arquivo, array $ambiente): Config
    {
        return new Config($this->lerArquivo($arquivo), $ambiente);
    }

    /** @return array<string, string> */
    private function lerArquivo(string $arquivo): array
    {
        if (!is_readable($arquivo)) {
            return [];
        }
        // o parser INI do PHP 8 não aceita "#" como comentário, que é o padrão de .env
        $conteudo = preg_replace('/^\s*#.*$/m', '', (string) file_get_contents($arquivo));
        // RAW: senhas com ! ; = são lidas literalmente; aspas externas são removidas
        $valores = parse_ini_string((string) $conteudo, false, INI_SCANNER_RAW);
        if ($valores === false) {
            throw new RuntimeException("Não foi possível interpretar o arquivo de configuração: {$arquivo}");
        }
        return array_map('strval', $valores);
    }
}
