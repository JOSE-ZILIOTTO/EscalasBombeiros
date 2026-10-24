<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Application\OrdemEscolha\ConsultarOrdemDeEscolha;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\MontadorDeQuadro;
use Escala\Domain\Escala\PlantaoRepository;

final class ConsultarDia
{
    public function __construct(
        private readonly BombeiroRepository $bombeiros,
        private readonly PlantaoRepository $plantoes,
        private readonly ExcecaoFixoRepository $excecoes,
        private readonly MontadorDeQuadro $montador,
        private readonly ConsultarOrdemDeEscolha $ordem,
    ) {
    }

    public function executar(string $data): DetalhesDoDia
    {
        $dia = DataEscala::deTexto($data);

        $quadro = $this->montador->montar(
            $dia,
            $this->plantoes->doDia($dia),
            $this->bombeiros->fixosAtivos(),
            $this->excecoes->doPeriodo($dia, $dia),
        );

        return new DetalhesDoDia($quadro, $this->bombeiros->ativos(), $this->ordem->executar()->sugerido);
    }
}
