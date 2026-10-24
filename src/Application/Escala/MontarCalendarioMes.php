<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Application\OrdemEscolha\ConsultarOrdemDeEscolha;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Escala\MontadorDeQuadro;
use Escala\Domain\Escala\PlantaoRepository;

final class MontarCalendarioMes
{
    public function __construct(
        private readonly BombeiroRepository $bombeiros,
        private readonly PlantaoRepository $plantoes,
        private readonly ExcecaoFixoRepository $excecoes,
        private readonly MontadorDeQuadro $montador,
        private readonly ConsultarOrdemDeEscolha $ordem,
    ) {
    }

    public function executar(MesEscala $mes): CalendarioDoMes
    {
        $inicio = $mes->primeiroDia();
        $fim = $mes->ultimoDia();

        // 3 consultas para o mês inteiro, em vez de várias por dia como no painel antigo
        $plantoesPorDia = [];
        foreach ($this->plantoes->doPeriodo($inicio, $fim) as $plantao) {
            $plantoesPorDia[$plantao->data->emTexto()][] = $plantao;
        }
        $fixos = $this->bombeiros->fixosAtivos();
        $excecoes = $this->excecoes->doPeriodo($inicio, $fim);

        $dias = [];
        foreach ($mes->dias() as $dia) {
            $dias[] = $this->montador->montar($dia, $plantoesPorDia[$dia->emTexto()] ?? [], $fixos, $excecoes);
        }

        return new CalendarioDoMes($mes, $dias, $this->ordem->executar());
    }
}
