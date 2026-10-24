<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class PlantaoDuplicado extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'Este bombeiro já está escalado para este dia/turno.')
    {
        parent::__construct($mensagem);
    }
}
