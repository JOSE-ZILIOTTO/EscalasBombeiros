<?php

declare(strict_types=1);

namespace Escala\Application\Exportacao;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\ExcecoesDoPeriodo;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Escala\Turno;

final class GerarPlanilhaMensal
{
    public function __construct(
        private readonly BombeiroRepository $bombeiros,
        private readonly PlantaoRepository $plantoes,
        private readonly ExcecaoFixoRepository $excecoes,
        private readonly CicloFixo $ciclo,
    ) {
    }

    public function executar(MesEscala $mes): PlanilhaMensal
    {
        $codigosPorBombeiro = [];
        foreach ($this->plantoes->doPeriodo($mes->primeiroDia(), $mes->ultimoDia()) as $plantao) {
            $codigosPorBombeiro[$plantao->bombeiroId][$plantao->data->dia()][] = $plantao->turno->codigoPlanilha();
        }
        $excecoes = $this->excecoes->doPeriodo($mes->primeiroDia(), $mes->ultimoDia());

        $linhas = [];
        foreach ($this->bombeiros->ativos() as $bombeiro) {
            $codigos = $codigosPorBombeiro[$bombeiro->id] ?? [];
            foreach ($this->diasDeCiclo($bombeiro, $mes, $excecoes) as $dia) {
                $codigos[$dia] = [Turno::Integral->codigoPlanilha(), ...($codigos[$dia] ?? [])];
            }
            $linhas[] = new LinhaDaPlanilha($bombeiro, array_map(
                static fn (array $lista): array => array_values(array_unique($lista)),
                $codigos,
            ));
        }

        return new PlanilhaMensal($mes, $linhas);
    }

    /** @return list<int> dias do mês em que o ciclo coloca o Fixo de serviço (sem exceção) */
    private function diasDeCiclo(Bombeiro $bombeiro, MesEscala $mes, ExcecoesDoPeriodo $excecoes): array
    {
        if (!$bombeiro->ehFixo() || $bombeiro->referenciaCiclo === null || $bombeiro->id === null) {
            return [];
        }
        $dias = [];
        foreach ($mes->dias() as $data) {
            if ($this->ciclo->estaDeServico($bombeiro->referenciaCiclo, $data) && !$excecoes->possui($bombeiro->id, $data)) {
                $dias[] = $data->dia();
            }
        }
        return $dias;
    }
}
