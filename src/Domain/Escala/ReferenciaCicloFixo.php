<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Excecao\CicloFixoInvalido;

/** Âncora do ciclo de um Fixo: "na data X ele estava no dia N do ciclo". */
final readonly class ReferenciaCicloFixo
{
    public function __construct(public DataEscala $data, public int $diaDoCiclo)
    {
        if ($diaDoCiclo < 1 || $diaDoCiclo > CicloFixo::DURACAO_EM_DIAS) {
            throw new CicloFixoInvalido();
        }
    }
}
