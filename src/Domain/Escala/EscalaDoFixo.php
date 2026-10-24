<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Bombeiro\Bombeiro;

final class EscalaDoFixo
{
    public function __construct(private readonly CicloFixo $ciclo)
    {
    }

    /**
     * Prefere o primeiro Fixo de serviço sem exceção. Se todos os escalados tiverem
     * exceção, devolve o primeiro mesmo assim (marcado), para a tela permitir restaurá-lo.
     *
     * @param list<Bombeiro> $fixosAtivos em ordem de id
     */
    public function fixoDoDia(DataEscala $data, array $fixosAtivos, ExcecoesDoPeriodo $excecoes): ?FixoDoDia
    {
        $primeiroComExcecao = null;

        foreach ($fixosAtivos as $fixo) {
            if ($fixo->referenciaCiclo === null || $fixo->id === null) {
                continue; // cadastro antigo sem referência: não há como calcular o ciclo
            }
            if (!$this->ciclo->estaDeServico($fixo->referenciaCiclo, $data)) {
                continue;
            }
            if (!$excecoes->possui($fixo->id, $data)) {
                return new FixoDoDia($fixo, false);
            }
            $primeiroComExcecao ??= new FixoDoDia($fixo, true);
        }
        return $primeiroComExcecao;
    }
}
