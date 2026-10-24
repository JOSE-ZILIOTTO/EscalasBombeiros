<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class UsuarioNaoEncontrado extends RecursoNaoEncontrado
{
    public function __construct(string $mensagem = 'Usuário não encontrado.')
    {
        parent::__construct($mensagem);
    }
}
