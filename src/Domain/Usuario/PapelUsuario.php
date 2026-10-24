<?php

declare(strict_types=1);

namespace Escala\Domain\Usuario;

use Escala\Domain\Excecao\PapelInvalido;

enum PapelUsuario: string
{
    case Admin = 'admin';
    case Bombeiro = 'bc';

    public static function deCodigo(string $codigo): self
    {
        return self::tryFrom(trim($codigo)) ?? throw new PapelInvalido();
    }
}
