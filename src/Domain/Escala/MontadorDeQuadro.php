<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Bombeiro\Bombeiro;

/**
 * Junta ciclo do Fixo + plantões + regra de vagas. Painel, tela de detalhes e registro
 * passam todos por aqui, o que impede as três telas de voltarem a divergir.
 */
final class MontadorDeQuadro
{
    public function __construct(
        private readonly EscalaDoFixo $escalaDoFixo,
        private readonly RegraDeVagas $regraDeVagas,
    ) {
    }

    /**
     * @param list<Plantao>  $plantoesDoDia
     * @param list<Bombeiro> $fixosAtivos
     */
    public function montar(DataEscala $data, array $plantoesDoDia, array $fixosAtivos, ExcecoesDoPeriodo $excecoes): QuadroDoDia
    {
        $fixo = $this->escalaDoFixo->fixoDoDia($data, $fixosAtivos, $excecoes);
        $vagas = $this->regraDeVagas->calcular($plantoesDoDia, $fixo !== null && $fixo->ehValido());

        return new QuadroDoDia($data, $fixo, $plantoesDoDia, $vagas);
    }
}
