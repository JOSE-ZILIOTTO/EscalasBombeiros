<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class FixoForaDeServico extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'Este bombeiro não está de serviço pelo ciclo nesta data.')
    {
        parent::__construct($mensagem);
    }
}
