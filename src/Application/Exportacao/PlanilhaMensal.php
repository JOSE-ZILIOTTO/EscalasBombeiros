<?php

declare(strict_types=1);

namespace Escala\Application\Exportacao;

use Escala\Domain\Escala\MesEscala;

final readonly class PlanilhaMensal
{
    /** @param list<LinhaDaPlanilha> $linhas uma por bombeiro ativo, por nome */
    public function __construct(public MesEscala $mes, public array $linhas)
    {
    }
}
