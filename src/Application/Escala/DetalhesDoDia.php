<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Escala\QuadroDoDia;

final readonly class DetalhesDoDia
{
    /** @param list<Bombeiro> $bombeirosAtivos opções do seletor "registrar plantão" */
    public function __construct(
        public QuadroDoDia $quadro,
        public array $bombeirosAtivos,
        public ?Bombeiro $sugerido,
    ) {
    }
}
