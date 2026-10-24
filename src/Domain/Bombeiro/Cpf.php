<?php

declare(strict_types=1);

namespace Escala\Domain\Bombeiro;

use Escala\Domain\Excecao\CpfInvalido;

final readonly class Cpf
{
    private function __construct(public string $formatado)
    {
    }

    /** Para CPFs digitados agora: exige 11 dígitos com dígitos verificadores corretos. */
    public static function criar(string $valor): self
    {
        $digitos = preg_replace('/\D/', '', $valor) ?? '';
        if (!self::digitosValidos($digitos)) {
            throw new CpfInvalido();
        }
        return new self(sprintf(
            '%s.%s.%s-%s',
            substr($digitos, 0, 3),
            substr($digitos, 3, 3),
            substr($digitos, 6, 3),
            substr($digitos, 9, 2),
        ));
    }

    /**
     * Para valores já gravados: aceita sem validar, porque cadastros antigos podem ter
     * CPF fora do padrão e não devem quebrar a leitura nem a edição de outros campos.
     */
    public static function doBanco(string $valor): self
    {
        return new self($valor);
    }

    public function igual(string $valor): bool
    {
        return trim($valor) === $this->formatado;
    }

    private static function digitosValidos(string $digitos): bool
    {
        if (strlen($digitos) !== 11 || preg_match('/^(\d)\1{10}$/', $digitos) === 1) {
            return false;
        }
        for ($posicao = 9; $posicao <= 10; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $digitos[$i] * ($posicao + 1 - $i);
            }
            $verificador = (($soma * 10) % 11) % 10;
            if ((int) $digitos[$posicao] !== $verificador) {
                return false;
            }
        }
        return true;
    }
}
