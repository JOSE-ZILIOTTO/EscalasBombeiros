<?php

declare(strict_types=1);

namespace Escala\Domain\Usuario;

use Escala\Domain\Excecao\UltimoAdministrador;

/**
 * O sistema nunca pode ficar sem um administrador ativo: sem ele, ninguém mais consegue
 * entrar no painel nem criar contas, e a recuperação só seria possível direto no banco.
 */
final class ProtecaoUltimoAdministrador
{
    /**
     * @param int $adminsAtivos total atual de admins ativos, contando com $antes
     * @throws UltimoAdministrador
     */
    public function verificar(Usuario $antes, Usuario $depois, int $adminsAtivos): void
    {
        $deixaDeSerAdminAtivo = $antes->ehAdminAtivo() && !$depois->ehAdminAtivo();
        if ($deixaDeSerAdminAtivo && $adminsAtivos <= 1) {
            throw new UltimoAdministrador();
        }
    }
}
