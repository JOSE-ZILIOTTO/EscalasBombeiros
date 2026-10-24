<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class BombeiroInativo extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'Este bombeiro está inativo e não pode receber plantões.')
    {
        parent::__construct($mensagem);
    }
}
