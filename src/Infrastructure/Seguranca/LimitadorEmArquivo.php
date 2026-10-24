<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Seguranca;

use Escala\Application\Auth\LimitadorDeTentativas;
use Escala\Application\Relogio;

/**
 * Guarda as falhas recentes de cada cliente em arquivo. Funciona em hospedagem
 * compartilhada sem APCu/Redis; para mais tráfego, basta outra implementação da interface.
 */
final class LimitadorEmArquivo implements LimitadorDeTentativas
{
    public function __construct(
        private readonly string $diretorio,
        private readonly Relogio $relogio,
        private readonly int $maximoDeFalhas = 5,
        private readonly int $janelaEmSegundos = 900,
    ) {
    }

    public function estaBloqueado(string $chave): bool
    {
        return count($this->falhasRecentes($chave)) >= $this->maximoDeFalhas;
    }

    public function registrarFalha(string $chave): void
    {
        $falhas = $this->falhasRecentes($chave);
        $falhas[] = $this->relogio->agora()->getTimestamp();
        @file_put_contents($this->arquivo($chave), json_encode($falhas), LOCK_EX);
    }

    public function limpar(string $chave): void
    {
        @unlink($this->arquivo($chave));
    }

    /** @return list<int> */
    private function falhasRecentes(string $chave): array
    {
        $arquivo = $this->arquivo($chave);
        $dados = is_file($arquivo) ? json_decode((string) @file_get_contents($arquivo), true) : null;
        $limite = $this->relogio->agora()->getTimestamp() - $this->janelaEmSegundos;

        return array_values(array_filter(
            is_array($dados) ? $dados : [],
            static fn (mixed $instante): bool => is_int($instante) && $instante > $limite,
        ));
    }

    private function arquivo(string $chave): string
    {
        // hash: a chave (IP) nunca vira parte de um caminho de arquivo
        return $this->diretorio . DIRECTORY_SEPARATOR . 'escala_login_' . hash('sha256', $chave) . '.json';
    }
}
