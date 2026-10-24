<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class TurnoInvalido extends DadoInvalido
{
    public function __construct(string $mensagem = 'Turno inválido.')
    {
        parent::__construct($mensagem);
    }
}
