<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class UsuarioJaExiste extends RegraDeNegocioViolada
{
    public function __construct(string $login)
    {
        parent::__construct("O usuário \"{$login}\" já existe. Marque \"Substituir se o usuário já existir\" para redefinir a senha e o perfil.");
    }
}
