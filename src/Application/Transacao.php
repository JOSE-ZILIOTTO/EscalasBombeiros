<?php

declare(strict_types=1);

namespace Escala\Application;

interface Transacao
{
    /**
     * Executa tudo ou nada: qualquer exceção desfaz as gravações e é relançada.
     *
     * @template T
     * @param callable(): T $operacao
     * @return T
     */
    public function executar(callable $operacao): mixed;
}
