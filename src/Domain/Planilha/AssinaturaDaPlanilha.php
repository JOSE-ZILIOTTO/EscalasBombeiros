<?php

declare(strict_types=1);

namespace Escala\Domain\Planilha;

use Escala\Domain\Excecao\AssinaturaInvalida;

/** Bloco de assinatura no rodapé da planilha impressa (responsável, cargo e unidade). */
final readonly class AssinaturaDaPlanilha
{
    public const TAMANHO_MAXIMO = 120;

    public string $nome;
    public string $cargo;
    public string $unidade;

    public function __construct(string $nome, string $cargo, string $unidade)
    {
        $this->nome = self::validar($nome, 'o nome do responsável', obrigatorio: true);
        $this->cargo = self::validar($cargo, 'o cargo', obrigatorio: true);
        $this->unidade = self::validar($unidade, 'a unidade', obrigatorio: false);
    }

    /** Texto usado antes de um administrador editar a assinatura. */
    public static function padrao(): self
    {
        return new self('Responsável pela escala', 'Coordenador BC', '');
    }

    private static function validar(string $valor, string $campo, bool $obrigatorio): string
    {
        if (!mb_check_encoding($valor, 'UTF-8')) {
            throw new AssinaturaInvalida("Há caracteres inválidos no texto: corrija {$campo}.");
        }
        $valor = trim((string) preg_replace('/\s+/u', ' ', $valor));
        if ($obrigatorio && $valor === '') {
            throw new AssinaturaInvalida("Informe {$campo}.");
        }
        if (mb_strlen($valor) > self::TAMANHO_MAXIMO) {
            throw new AssinaturaInvalida(sprintf('Use no máximo %d caracteres para %s.', self::TAMANHO_MAXIMO, $campo));
        }
        return $valor;
    }
}
