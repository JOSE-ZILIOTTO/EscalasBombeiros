<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class BombeiroNaoEFixo extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'A exceção de ciclo só se aplica a bombeiros Fixos ativos.')
    {
        parent::__construct($mensagem);
    }
}
