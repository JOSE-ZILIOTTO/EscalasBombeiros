<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class BombeiroNaoEncontrado extends RecursoNaoEncontrado
{
    public function __construct(string $mensagem = 'Bombeiro não encontrado.')
    {
        parent::__construct($mensagem);
    }
}
