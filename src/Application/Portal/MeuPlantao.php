<?php

declare(strict_types=1);

namespace Escala\Application\Portal;

use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Shared\Dinheiro;

final readonly class MeuPlantao
{
    public function __construct(
        public DataEscala $data,
        public Turno $turno,
        /** null para o Fixo: o salário dele não segue a tabela por plantão. */
        public ?Dinheiro $valor,
        /** Data de hoje ou anterior. */
        public bool $realizado,
        /** Dia de serviço do Fixo pelo ciclo 24x72, sem plantão registrado. */
        public bool $doCiclo = false,
    ) {
    }

    public function horas(): int
    {
        return $this->turno->horas();
    }
}
