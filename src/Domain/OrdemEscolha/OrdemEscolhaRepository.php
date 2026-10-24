<?php

declare(strict_types=1);

namespace Escala\Domain\OrdemEscolha;

interface OrdemEscolhaRepository
{
    public function carregarEstado(): EstadoOrdemEscolha;

    public function salvarEstado(EstadoOrdemEscolha $estado): void;
}
