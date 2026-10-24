<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Bombeiro\TipoBombeiro;

/** Plantão já registrado, com o resumo do bombeiro necessário para as regras e telas. */
final readonly class Plantao
{
    public function __construct(
        public int $id,
        public int $bombeiroId,
        public string $nomeBombeiro,
        public TipoBombeiro $tipoBombeiro,
        public DataEscala $data,
        public Turno $turno,
    ) {
    }
}
