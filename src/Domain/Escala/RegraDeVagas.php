<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Excecao\SubstitutoNaoPermitido;
use Escala\Domain\Excecao\VagaIndisponivel;

/**
 * Fonte única da regra de vagas:
 * - por dia há N vagas de BC no Diurno e N no Noturno; o Integral de um BC ocupa as duas;
 * - plantões de Fixo (trocas/coberturas) não ocupam vaga de BC;
 * - sem Fixo válido no dia abre 1 vaga de Integral Substituto, ocupável por BC ou Fixo.
 */
final class RegraDeVagas
{
    public function __construct(private readonly int $vagasBcPorTurno = 1)
    {
    }

    /** @param list<Plantao> $plantoesDoDia */
    public function calcular(array $plantoesDoDia, bool $fixoValido): Vagas
    {
        $diurno = $this->vagasBcPorTurno;
        $noturno = $this->vagasBcPorTurno;
        $substituto = $fixoValido ? 0 : 1;

        foreach ($plantoesDoDia as $plantao) {
            if ($plantao->turno === Turno::IntegralSubstituto) {
                $substituto--;
                continue;
            }
            if ($plantao->tipoBombeiro !== TipoBombeiro::Bc) {
                continue;
            }
            if ($plantao->turno->ocupaDiurno()) {
                $diurno--;
            }
            if ($plantao->turno->ocupaNoturno()) {
                $noturno--;
            }
        }
        return new Vagas($diurno, $noturno, $substituto);
    }

    public function garantirDisponibilidade(Turno $turno, TipoBombeiro $tipo, QuadroDoDia $quadro): void
    {
        $vagas = $quadro->vagas;

        if ($turno === Turno::IntegralSubstituto) {
            if ($quadro->temFixoValido()) {
                throw new SubstitutoNaoPermitido();
            }
            if ($vagas->substituto <= 0) {
                throw VagaIndisponivel::paraSubstituto();
            }
            return;
        }

        if ($tipo !== TipoBombeiro::Bc) {
            return; // Fixo em D/N/I é troca; duplicidade é barrada pela chave única do banco
        }

        $semVaga = ($turno->ocupaDiurno() && $vagas->diurno <= 0)
            || ($turno->ocupaNoturno() && $vagas->noturno <= 0);
        if ($semVaga) {
            throw VagaIndisponivel::paraBc();
        }
    }
}
