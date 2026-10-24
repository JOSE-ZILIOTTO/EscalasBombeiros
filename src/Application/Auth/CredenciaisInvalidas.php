<?php

declare(strict_types=1);

namespace Escala\Application\Auth;

use Escala\Domain\Excecao\RegraDeNegocioViolada;

/** Mensagem única para usuário inexistente, inativo ou senha errada: não revela qual foi. */
final class CredenciaisInvalidas extends RegraDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('Usuário ou senha inválidos. Tente novamente.');
    }
}
