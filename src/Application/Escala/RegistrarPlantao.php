<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Application\Transacao;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\MontadorDeQuadro;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Escala\RegraDeVagas;
use Escala\Domain\Escala\RegraDoPlantonista;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Excecao\BombeiroNaoEncontrado;
use Escala\Domain\Excecao\CampoObrigatorio;

final class RegistrarPlantao
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly BombeiroRepository $bombeiros,
        private readonly PlantaoRepository $plantoes,
        private readonly ExcecaoFixoRepository $excecoes,
        private readonly MontadorDeQuadro $montador,
        private readonly RegraDeVagas $regraDeVagas,
        private readonly RegraDoPlantonista $regraDoPlantonista,
    ) {
    }

    public function executar(RegistrarPlantaoEntrada $entrada): int
    {
        if ($entrada->bombeiroId <= 0 || trim($entrada->data) === '' || trim($entrada->turno) === '') {
            throw new CampoObrigatorio();
        }
        $data = DataEscala::deTexto($entrada->data);
        $turno = Turno::deCodigo($entrada->turno);

        return $this->transacao->executar(function () use ($entrada, $data, $turno): int {
            $bombeiro = $this->bombeiros->buscar($entrada->bombeiroId) ?? throw new BombeiroNaoEncontrado();

            // a leitura com trava acontece dentro da transação para que a checagem de
            // vagas e o INSERT sejam atômicos frente a outro admin registrando ao mesmo tempo
            $plantoesDoDia = $this->plantoes->doDia($data, bloquearParaEscrita: true);
            $this->regraDoPlantonista->garantir($bombeiro, $data, $turno, $plantoesDoDia);

            $quadro = $this->montador->montar(
                $data,
                $plantoesDoDia,
                $this->bombeiros->fixosAtivos(),
                $this->excecoes->doPeriodo($data, $data),
            );
            $this->regraDeVagas->garantirDisponibilidade($turno, $bombeiro->tipo, $quadro);

            return $this->plantoes->inserir($entrada->bombeiroId, $data, $turno);
        });
    }
}
