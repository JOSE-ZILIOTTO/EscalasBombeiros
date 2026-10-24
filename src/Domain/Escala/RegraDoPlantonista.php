<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Excecao\BombeiroInativo;
use Escala\Domain\Excecao\FixoEmDiaDeServico;
use Escala\Domain\Excecao\TurnoSobreposto;

/**
 * Regras sobre quem recebe o plantão (a RegraDeVagas cuida de haver vaga):
 * - só bombeiro ativo;
 * - o Fixo não recebe plantão no próprio dia de ciclo: presente, já está de 24h; com
 *   ausência marcada, não pode ser o substituto da própria ausência;
 * - a mesma pessoa não pode ter dois turnos com horário em comum no mesmo dia.
 */
final class RegraDoPlantonista
{
    public function __construct(private readonly CicloFixo $ciclo)
    {
    }

    /** @param list<Plantao> $plantoesDoDia todos os plantões da data */
    public function garantir(Bombeiro $bombeiro, DataEscala $data, Turno $turno, array $plantoesDoDia): void
    {
        if (!$bombeiro->ativo) {
            throw new BombeiroInativo();
        }
        if ($bombeiro->ehFixo() && $bombeiro->referenciaCiclo !== null
            && $this->ciclo->estaDeServico($bombeiro->referenciaCiclo, $data)) {
            throw new FixoEmDiaDeServico();
        }
        foreach ($plantoesDoDia as $plantao) {
            if ($plantao->bombeiroId === $bombeiro->id && $plantao->turno->sobrepoe($turno)) {
                throw new TurnoSobreposto();
            }
        }
    }
}
