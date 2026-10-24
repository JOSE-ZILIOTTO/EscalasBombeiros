<?php

declare(strict_types=1);

namespace Escala\Domain\Bombeiro;

use Escala\Domain\Excecao\TipoBombeiroInvalido;

enum TipoBombeiro: string
{
    /** Voluntário: escolhe plantões seguindo a ordem de escolha. */
    case Bc = 'BC';
    /** Efetivo: trabalha pelo ciclo automático 24x72. */
    case Fixo = 'Fixo';

    public static function deCodigo(string $codigo): self
    {
        return self::tryFrom(trim($codigo)) ?? throw new TipoBombeiroInvalido();
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Bc => 'Voluntário',
            self::Fixo => 'Fixo',
        };
    }
}
