<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class VagaIndisponivel extends RegraDeNegocioViolada
{
    public static function paraBc(): self
    {
        return new self('Não há mais vagas de BC para o turno selecionado.');
    }

    public static function paraSubstituto(): self
    {
        return new self('A vaga Integral (Substituto) já está ocupada.');
    }
}
