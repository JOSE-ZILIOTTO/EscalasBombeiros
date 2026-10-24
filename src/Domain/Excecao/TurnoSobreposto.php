<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class TurnoSobreposto extends RegraDeNegocioViolada
{
    public function __construct(string $mensagem = 'Este bombeiro já tem um plantão neste dia que cobre o mesmo horário.')
    {
        parent::__construct($mensagem);
    }
}
