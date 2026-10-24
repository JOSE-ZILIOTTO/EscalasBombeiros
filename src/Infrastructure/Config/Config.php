<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Config;

/** Configuração imutável; variável de ambiente real do servidor tem prioridade sobre o .env. */
final readonly class Config
{
    /**
     * @param array<string, string> $arquivo  valores lidos do .env
     * @param array<string, string> $ambiente variáveis de ambiente do processo
     */
    public function __construct(private array $arquivo, private array $ambiente = [])
    {
    }

    public function texto(string $chave, ?string $padrao = null): ?string
    {
        return $this->ambiente[$chave] ?? $this->arquivo[$chave] ?? $padrao;
    }

    public function textoObrigatorio(string $chave): string
    {
        $valor = $this->texto($chave);
        if ($valor === null || $valor === '') {
            throw new ConfiguracaoAusente($chave);
        }
        return $valor;
    }

    public function inteiro(string $chave, int $padrao): int
    {
        $valor = filter_var($this->texto($chave), FILTER_VALIDATE_INT);
        return $valor === false ? $padrao : $valor;
    }
}
