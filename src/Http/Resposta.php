<?php

declare(strict_types=1);

namespace Escala\Http;

interface Resposta
{
    public function status(): int;

    public function enviar(): void;
}
