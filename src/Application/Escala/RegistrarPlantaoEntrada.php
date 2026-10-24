<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

/** Dados brutos vindos do formulário; a validação acontece no caso de uso. */
final readonly class RegistrarPlantaoEntrada
{
    public function __construct(public int $bombeiroId, public string $data, public string $turno)
    {
    }
}
