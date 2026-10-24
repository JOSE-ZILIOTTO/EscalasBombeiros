<?php

declare(strict_types=1);

namespace Escala\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Escala\Application\Relogio;

final class RelogioDoSistema implements Relogio
{
    public function __construct(private readonly DateTimeZone $fuso)
    {
    }

    public function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->fuso);
    }
}
