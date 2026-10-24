<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Excecao\PlantaoDuplicado;

interface PlantaoRepository
{
    /**
     * @param bool $bloquearParaEscrita trava as linhas do dia até o fim da transação,
     *                                  para dois registros simultâneos não ocuparem a mesma vaga
     * @return list<Plantao>
     */
    public function doDia(DataEscala $data, bool $bloquearParaEscrita = false): array;

    /** @return list<Plantao> ordenados por data e turno */
    public function doPeriodo(DataEscala $inicio, DataEscala $fim): array;

    /** @return list<Plantao> só os do bombeiro, ordenados por data e turno */
    public function doBombeiro(int $bombeiroId, DataEscala $inicio, DataEscala $fim): array;

    /** @throws PlantaoDuplicado */
    public function inserir(int $bombeiroId, DataEscala $data, Turno $turno): int;

    /** @return bool false se o plantão não existia */
    public function remover(int $plantaoId): bool;
}
