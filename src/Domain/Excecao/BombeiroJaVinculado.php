<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class BombeiroJaVinculado extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'Esse bombeiro já está vinculado a outro usuário.')
    {
        parent::__construct($mensagem);
    }
}
