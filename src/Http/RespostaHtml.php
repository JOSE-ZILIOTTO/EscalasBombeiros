<?php

declare(strict_types=1);

namespace Escala\Http;

final readonly class RespostaHtml implements Resposta
{
    public function __construct(public string $html, private int $status = 200)
    {
    }

    public function status(): int
    {
        return $this->status;
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        header('Content-Type: text/html; charset=utf-8');
        echo $this->html;
    }
}
