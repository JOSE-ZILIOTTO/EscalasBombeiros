<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class PlantaoNaoEncontrado extends RecursoNaoEncontrado
{
    public function __construct(string $mensagem = 'Plantão não encontrado.')
    {
        parent::__construct($mensagem);
    }
}
