<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class UltimoAdministrador extends RegraDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct(
            'Este é o último administrador ativo. Cadastre ou ative outro administrador antes de desativá-lo ou mudar o perfil dele.'
        );
    }
}
