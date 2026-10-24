<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

interface ExcecaoFixoRepository
{
    public function doPeriodo(DataEscala $inicio, DataEscala $fim): ExcecoesDoPeriodo;

    /** Idempotente: registrar duas vezes a mesma exceção não é erro. */
    public function registrar(int $bombeiroId, DataEscala $data): void;

    public function remover(int $bombeiroId, DataEscala $data): void;
}
