<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class SubstitutoNaoPermitido extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'A vaga I_SUB só existe quando não há fixo válido no dia.')
    {
        parent::__construct($mensagem);
    }
}
