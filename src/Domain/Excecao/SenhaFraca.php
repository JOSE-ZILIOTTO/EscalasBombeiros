<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class SenhaFraca extends DadoInvalido
{
    public function __construct(string $mensagem = 'A senha deve ter pelo menos 8 caracteres.')
    {
        parent::__construct($mensagem);
    }
}
