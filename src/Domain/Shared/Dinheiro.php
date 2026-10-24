<?php

declare(strict_types=1);

namespace Escala\Domain\Shared;

use InvalidArgumentException;

/** Valor monetário em centavos (inteiro), para evitar erros de arredondamento de float. */
final readonly class Dinheiro
{
    private function __construct(public int $centavos)
    {
        if ($centavos < 0) {
            throw new InvalidArgumentException('Valor monetário não pode ser negativo.');
        }
    }

    public static function centavos(int $centavos): self
    {
        return new self($centavos);
    }

    public static function reais(int $reais): self
    {
        return new self($reais * 100);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function somar(self $outro): self
    {
        return new self($this->centavos + $outro->centavos);
    }

    /** Reais como int quando não há centavos, para manter o JSON atual (ex.: 250 e não 250.0). */
    public function emReais(): int|float
    {
        return $this->centavos % 100 === 0 ? intdiv($this->centavos, 100) : $this->centavos / 100;
    }
}
