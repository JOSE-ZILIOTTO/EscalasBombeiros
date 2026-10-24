<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Excecao\CampoObrigatorio;

/** Restaura o Fixo ao ciclo naquela data. */
final class RemoverExcecaoFixo
{
    public function __construct(private readonly ExcecaoFixoRepository $excecoes)
    {
    }

    public function executar(int $bombeiroId, string $data): void
    {
        if ($bombeiroId <= 0 || trim($data) === '') {
            throw new CampoObrigatorio('ID bombeiro ou data não fornecidos.');
        }
        $this->excecoes->remover($bombeiroId, DataEscala::deTexto($data));
    }
}
