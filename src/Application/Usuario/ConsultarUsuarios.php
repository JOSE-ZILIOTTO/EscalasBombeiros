<?php

declare(strict_types=1);

namespace Escala\Application\Usuario;

use Escala\Domain\Excecao\UsuarioNaoEncontrado;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;

final class ConsultarUsuarios
{
    public function __construct(private readonly UsuarioRepository $usuarios)
    {
    }

    /** @return list<Usuario> */
    public function todos(): array
    {
        return $this->usuarios->todos();
    }

    public function buscar(int $id): Usuario
    {
        return $this->usuarios->buscarPorId($id) ?? throw new UsuarioNaoEncontrado();
    }

    public function adminsAtivos(): int
    {
        return $this->usuarios->contarAdminsAtivos();
    }
}
