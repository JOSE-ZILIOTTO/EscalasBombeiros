<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class AnonimizacaoNaoPermitida extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'Inative o bombeiro antes de apagar os dados pessoais dele.')
    {
        parent::__construct($mensagem);
    }
}
