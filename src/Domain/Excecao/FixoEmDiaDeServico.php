<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class FixoEmDiaDeServico extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'Este Fixo já está de serviço pelo ciclo nesta data (24h). Se ele não vier, marque a ausência e escale outro bombeiro como substituto.')
    {
        parent::__construct($mensagem);
    }
}
