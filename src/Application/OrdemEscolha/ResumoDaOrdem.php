<?php

declare(strict_types=1);

namespace Escala\Application\OrdemEscolha;

use Escala\Domain\Bombeiro\Bombeiro;

final readonly class ResumoDaOrdem
{
    /** @param list<Bombeiro> $sequencia */
    public function __construct(
        public array $sequencia,
        public ?Bombeiro $primeiro,
        public ?Bombeiro $sugerido,
        public ?int $inicioSelecionado,
    ) {
    }
}
