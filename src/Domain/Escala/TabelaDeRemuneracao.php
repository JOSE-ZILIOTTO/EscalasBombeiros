<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Shared\Dinheiro;

/** Valor pago por turno conforme a duração; os valores vêm da configuração. */
final readonly class TabelaDeRemuneracao
{
    public function __construct(public Dinheiro $turno12Horas, public Dinheiro $turno24Horas)
    {
    }

    public function valorDe(Turno $turno): Dinheiro
    {
        return $turno->horas() === 24 ? $this->turno24Horas : $this->turno12Horas;
    }
}
