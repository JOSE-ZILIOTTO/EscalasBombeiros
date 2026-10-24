<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class CampoObrigatorio extends DadoInvalido
{
    public function __construct(string $mensagem = 'Dados incompletos.')
    {
        parent::__construct($mensagem);
    }
}
