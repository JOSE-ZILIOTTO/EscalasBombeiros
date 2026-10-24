<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Bombeiro\Bombeiro;

/** O Fixo que o ciclo escala para o dia; com exceção registrada, ele não comparece. */
final readonly class FixoDoDia
{
    public function __construct(public Bombeiro $bombeiro, public bool $temExcecao)
    {
    }

    public function ehValido(): bool
    {
        return !$this->temExcecao;
    }
}
