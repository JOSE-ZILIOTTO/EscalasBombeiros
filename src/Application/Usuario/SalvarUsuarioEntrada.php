<?php

declare(strict_types=1);

namespace Escala\Application\Usuario;

final readonly class SalvarUsuarioEntrada
{
    public function __construct(
        public string $nome,
        public string $login,
        public string $senha,
        public string $papel,
        public bool $ativo,
        /** Sem isso, um login já existente é recusado em vez de sobrescrito. */
        public bool $sobrescrever,
        public ?int $bombeiroId,
    ) {
    }
}
