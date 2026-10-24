<?php

declare(strict_types=1);

namespace Escala\Http;

/** Arquivo para download (ex.: planilha .xlsx). */
final readonly class RespostaArquivo implements Resposta
{
    public function __construct(
        public string $conteudo,
        public string $nomeDoArquivo,
        public string $tipo,
    ) {
    }

    public function status(): int
    {
        return 200;
    }

    public function enviar(): void
    {
        // nome ASCII de reserva + nome UTF-8 (RFC 6266) para navegadores que aceitam acentos
        $ascii = preg_replace('/[^A-Za-z0-9 ._-]/', '_', $this->nomeDoArquivo);
        http_response_code(200);
        header('Content-Type: ' . $this->tipo);
        header("Content-Disposition: attachment; filename=\"{$ascii}\"; filename*=UTF-8''" . rawurlencode($this->nomeDoArquivo));
        header('Content-Length: ' . strlen($this->conteudo));
        header('Cache-Control: private, no-store');
        echo $this->conteudo;
    }
}
