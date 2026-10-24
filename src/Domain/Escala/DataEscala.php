<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use DateTimeImmutable;
use Escala\Domain\Excecao\DataInvalida;

/** Um dia do calendário de escala, sem hora. */
final readonly class DataEscala
{
    private const FORMATO = 'Y-m-d';

    private function __construct(private DateTimeImmutable $data)
    {
    }

    public static function deTexto(string $texto): self
    {
        $texto = trim($texto);
        $data = DateTimeImmutable::createFromFormat('!' . self::FORMATO, $texto);
        // a releitura recusa datas "corrigidas" pelo PHP, como 2025-02-30 → 2025-03-02
        if ($data === false || $data->format(self::FORMATO) !== $texto) {
            throw new DataInvalida();
        }
        return new self($data);
    }

    public static function de(int $ano, int $mes, int $dia): self
    {
        return self::deTexto(sprintf('%04d-%02d-%02d', $ano, $mes, $dia));
    }

    public function emTexto(): string
    {
        return $this->data->format(self::FORMATO);
    }

    public function dia(): int
    {
        return (int) $this->data->format('j');
    }

    /** 0 = domingo … 6 = sábado. */
    public function diaDaSemana(): int
    {
        return (int) $this->data->format('w');
    }

    public function ehFimDeSemana(): bool
    {
        return in_array($this->diaDaSemana(), [0, 6], true);
    }

    /** Diferença em dias até $outra (negativa se $outra for anterior). */
    public function diasAte(self $outra): int
    {
        return (int) $this->data->diff($outra->data)->format('%r%a');
    }
}
