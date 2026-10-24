<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Excecao\CampoObrigatorio;
use Escala\Domain\Excecao\PlantaoNaoEncontrado;

final class RemoverPlantao
{
    public function __construct(private readonly PlantaoRepository $plantoes)
    {
    }

    public function executar(int $plantaoId): void
    {
        if ($plantaoId <= 0) {
            throw new CampoObrigatorio('ID inválido.');
        }
        if (!$this->plantoes->remover($plantaoId)) {
            throw new PlantaoNaoEncontrado();
        }
    }
}
