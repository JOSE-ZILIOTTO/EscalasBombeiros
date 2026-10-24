<?php

declare(strict_types=1);

namespace Escala\Application;

use DateTimeImmutable;

/** Abstrai "agora" para que regras dependentes de data sejam testáveis. */
interface Relogio
{
    public function agora(): DateTimeImmutable;
}
