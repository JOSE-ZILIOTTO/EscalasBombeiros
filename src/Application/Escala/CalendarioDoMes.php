<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Application\OrdemEscolha\ResumoDaOrdem;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Escala\QuadroDoDia;

final readonly class CalendarioDoMes
{
    /** @param list<QuadroDoDia> $dias um por dia do mês, em ordem */
    public function __construct(
        public MesEscala $mes,
        public array $dias,
        public ResumoDaOrdem $ordem,
    ) {
    }
}
