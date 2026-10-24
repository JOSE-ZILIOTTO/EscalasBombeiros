<?php

declare(strict_types=1);

namespace Escala\Http;

final readonly class Redirecionamento implements Resposta
{
    public function __construct(public string $destino)
    {
    }

    public function status(): int
    {
        return 302;
    }

    public function enviar(): void
    {
        header('Location: ' . $this->destino, true, 302);
    }
}
