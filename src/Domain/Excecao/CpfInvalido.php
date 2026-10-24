<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class CpfInvalido extends DadoInvalido
{
    public function __construct(string $mensagem = 'CPF inválido.')
    {
        parent::__construct($mensagem);
    }
}
