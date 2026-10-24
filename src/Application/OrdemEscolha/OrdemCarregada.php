<?php

declare(strict_types=1);

namespace Escala\Application\OrdemEscolha;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\OrdemEscolha\OrdemDeEscolha;

/** A ordem de escolha junto com os BCs que ela referencia, para resolver ids em nomes. */
final readonly class OrdemCarregada
{
    /** @param array<int, Bombeiro> $bcsPorId */
    public function __construct(public OrdemDeEscolha $ordem, private array $bcsPorId)
    {
    }

    public function bombeiro(?int $id): ?Bombeiro
    {
        return $id === null ? null : ($this->bcsPorId[$id] ?? null);
    }

    /** @return list<Bombeiro> */
    public function sequencia(): array
    {
        return array_values(array_filter(array_map($this->bombeiro(...), $this->ordem->sequencia())));
    }
}
