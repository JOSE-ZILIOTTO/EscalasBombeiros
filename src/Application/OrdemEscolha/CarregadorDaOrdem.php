<?php

declare(strict_types=1);

namespace Escala\Application\OrdemEscolha;

use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\OrdemEscolha\OrdemDeEscolha;
use Escala\Domain\OrdemEscolha\OrdemEscolhaRepository;

/** Monta a OrdemDeEscolha a partir dos dois repositórios; evita repetir isso em cada caso de uso. */
final class CarregadorDaOrdem
{
    public function __construct(
        private readonly BombeiroRepository $bombeiros,
        private readonly OrdemEscolhaRepository $ordens,
    ) {
    }

    public function carregar(): OrdemCarregada
    {
        $bcsPorId = [];
        foreach ($this->bombeiros->bcsAtivos() as $bc) {
            if ($bc->id !== null) {
                $bcsPorId[$bc->id] = $bc;
            }
        }
        $ordem = new OrdemDeEscolha(array_keys($bcsPorId), $this->ordens->carregarEstado());

        return new OrdemCarregada($ordem, $bcsPorId);
    }

    public function salvar(OrdemDeEscolha $ordem): void
    {
        $this->ordens->salvarEstado($ordem->estado);
    }
}
