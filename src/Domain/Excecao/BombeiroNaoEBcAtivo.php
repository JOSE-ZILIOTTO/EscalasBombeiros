<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class BombeiroNaoEBcAtivo extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'BC não encontrado/ativo.')
    {
        parent::__construct($mensagem);
    }
}
