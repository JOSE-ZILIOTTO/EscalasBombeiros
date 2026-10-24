<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

/** Situação completa de um dia: quem o ciclo escala, quem se registrou e o que sobrou de vagas. */
final readonly class QuadroDoDia
{
    /** @param list<Plantao> $plantoes */
    public function __construct(
        public DataEscala $data,
        public ?FixoDoDia $fixo,
        public array $plantoes,
        public Vagas $vagas,
    ) {
    }

    public function temFixoValido(): bool
    {
        return $this->fixo !== null && $this->fixo->ehValido();
    }
}
