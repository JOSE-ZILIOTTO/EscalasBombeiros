<?php

declare(strict_types=1);

namespace Escala\Application\Exportacao;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Escala\Turno;

final readonly class LinhaDaPlanilha
{
    /** @param array<int, list<string>> $codigosPorDia dia do mês => códigos I/D/N (vazio = sem plantão) */
    public function __construct(public Bombeiro $bombeiro, public array $codigosPorDia)
    {
    }

    /** @return list<string> */
    public function codigos(int $dia): array
    {
        return $this->codigosPorDia[$dia] ?? [];
    }

    /** Total de horas do mês: I conta 24h; D e N, 12h cada. */
    public function horas(): int
    {
        $horas = 0;
        foreach ($this->codigosPorDia as $codigos) {
            foreach ($codigos as $codigo) {
                $horas += Turno::deCodigo($codigo)->horas();
            }
        }
        return $horas;
    }
}
