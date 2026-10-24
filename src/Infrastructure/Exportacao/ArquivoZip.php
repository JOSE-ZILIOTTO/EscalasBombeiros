<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Exportacao;

/**
 * Zip mínimo, sem compressão (método "stored"), montado em memória. Evita depender da
 * extensão zip, que nem toda hospedagem gratuita oferece; para um .xlsx de poucos KB a
 * falta de compressão não importa.
 */
final class ArquivoZip
{
    /** @var list<array{nome: string, tamanho: int, crc: int, deslocamento: int}> */
    private array $entradas = [];
    private string $dados = '';

    public function adicionar(string $nome, string $conteudo): void
    {
        $crc = crc32($conteudo);
        $tamanho = strlen($conteudo);
        $this->entradas[] = ['nome' => $nome, 'tamanho' => $tamanho, 'crc' => $crc, 'deslocamento' => strlen($this->dados)];
        // cabeçalho local: versão 2.0, flag 0x0800 (nome em UTF-8), método 0 (sem compressão), data fixa
        $this->dados .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, 0, 0x21, $crc, $tamanho, $tamanho, strlen($nome), 0)
            . $nome . $conteudo;
    }

    public function conteudo(): string
    {
        $diretorio = '';
        foreach ($this->entradas as $e) {
            $diretorio .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50, 20, 20, 0x0800, 0, 0, 0x21, $e['crc'], $e['tamanho'], $e['tamanho'],
                strlen($e['nome']), 0, 0, 0, 0, 0, $e['deslocamento'],
            ) . $e['nome'];
        }
        $total = count($this->entradas);
        $fim = pack('VvvvvVVv', 0x06054b50, 0, 0, $total, $total, strlen($diretorio), strlen($this->dados), 0);
        return $this->dados . $diretorio . $fim;
    }
}
