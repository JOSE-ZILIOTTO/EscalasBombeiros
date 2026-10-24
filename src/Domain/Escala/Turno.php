<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use Escala\Domain\Excecao\TurnoInvalido;

enum Turno: string
{
    case Diurno = 'D';
    case Noturno = 'N';
    case Integral = 'I';
    /** Integral que cobre a ausência do Fixo do dia. */
    case IntegralSubstituto = 'I_SUB';

    public static function deCodigo(string $codigo): self
    {
        return self::tryFrom(trim($codigo)) ?? throw new TurnoInvalido();
    }

    public function horas(): int
    {
        return match ($this) {
            self::Diurno, self::Noturno => 12,
            self::Integral, self::IntegralSubstituto => 24,
        };
    }

    /** Se este turno consome a vaga diurna de BC. */
    public function ocupaDiurno(): bool
    {
        return $this === self::Diurno || $this === self::Integral;
    }

    /** Se este turno consome a vaga noturna de BC. */
    public function ocupaNoturno(): bool
    {
        return $this === self::Noturno || $this === self::Integral;
    }

    /** Se os dois turnos têm horário em comum: o de 24h cobre o dia todo; D e N não se cruzam. */
    public function sobrepoe(self $outro): bool
    {
        return $this->horas() === 24 || $outro->horas() === 24 || $this === $outro;
    }

    /** Código usado na planilha impressa, que só conhece I/D/N. */
    public function codigoPlanilha(): string
    {
        return $this === self::IntegralSubstituto ? self::Integral->value : $this->value;
    }
}
