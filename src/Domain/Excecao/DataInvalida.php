<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class DataInvalida extends DadoInvalido
{
    public function __construct(string $mensagem = 'Data inválida.')
    {
        parent::__construct($mensagem);
    }
}
