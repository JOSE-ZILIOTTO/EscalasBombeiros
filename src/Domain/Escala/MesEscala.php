<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

use DateTimeImmutable;
use Escala\Domain\Excecao\DataInvalida;

final readonly class MesEscala
{
    public const ANO_MINIMO = 1970;
    public const ANO_MAXIMO = 2100;

    private const NOMES = [
        1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    private function __construct(public int $ano, public int $mes)
    {
    }

    public static function de(int $ano, int $mes): self
    {
        if ($mes < 1 || $mes > 12 || $ano < self::ANO_MINIMO || $ano > self::ANO_MAXIMO) {
            throw new DataInvalida('Mês ou ano inválido.');
        }
        return new self($ano, $mes);
    }

    /**
     * Interpreta ?month=&year= da URL. Valor ausente ou fora da faixa cai no mês/ano
     * atual, campo a campo — mesmo comportamento das telas antigas.
     */
    public static function deParametros(?string $ano, ?string $mes, DateTimeImmutable $hoje): self
    {
        $anoInt = filter_var($ano, FILTER_VALIDATE_INT, ['options' => ['min_range' => self::ANO_MINIMO, 'max_range' => self::ANO_MAXIMO]]);
        $mesInt = filter_var($mes, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);

        return new self(
            $anoInt === false ? (int) $hoje->format('Y') : $anoInt,
            $mesInt === false ? (int) $hoje->format('n') : $mesInt,
        );
    }

    public function primeiroDia(): DataEscala
    {
        return DataEscala::de($this->ano, $this->mes, 1);
    }

    public function ultimoDia(): DataEscala
    {
        return DataEscala::de($this->ano, $this->mes, $this->quantidadeDeDias());
    }

    public function quantidadeDeDias(): int
    {
        return (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $this->ano, $this->mes)))->format('t');
    }

    /** @return list<DataEscala> */
    public function dias(): array
    {
        $dias = [];
        for ($dia = 1; $dia <= $this->quantidadeDeDias(); $dia++) {
            $dias[] = DataEscala::de($this->ano, $this->mes, $dia);
        }
        return $dias;
    }

    public function anterior(): self
    {
        return $this->mes === 1 ? new self($this->ano - 1, 12) : new self($this->ano, $this->mes - 1);
    }

    public function proximo(): self
    {
        return $this->mes === 12 ? new self($this->ano + 1, 1) : new self($this->ano, $this->mes + 1);
    }

    public function nome(): string
    {
        return self::NOMES[$this->mes];
    }
}
