<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class TipoBombeiroInvalido extends DadoInvalido
{
    public function __construct(string $mensagem = 'Tipo inválido.')
    {
        parent::__construct($mensagem);
    }
}
