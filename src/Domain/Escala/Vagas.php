<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

/** Vagas restantes em um dia; nunca negativas, mesmo com dados inconsistentes no banco. */
final readonly class Vagas
{
    public int $diurno;
    public int $noturno;
    public int $substituto;

    public function __construct(int $diurno, int $noturno, int $substituto)
    {
        $this->diurno = max(0, $diurno);
        $this->noturno = max(0, $noturno);
        $this->substituto = max(0, $substituto);
    }

    public function permiteIntegral(): bool
    {
        return $this->diurno > 0 && $this->noturno > 0;
    }

    public function temVagaDeBc(): bool
    {
        return $this->diurno > 0 || $this->noturno > 0;
    }
}
