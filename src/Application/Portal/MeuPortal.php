<?php

declare(strict_types=1);

namespace Escala\Application\Portal;

use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Escala\TabelaDeRemuneracao;
use Escala\Domain\Shared\Dinheiro;

/** Tudo aqui é do mês exibido: ao navegar entre meses, todos os números mudam juntos. */
final readonly class MeuPortal
{
    /** @param list<MeuPlantao> $plantoesDoMes */
    public function __construct(
        /** false quando o login não está ligado a um bombeiro: não há plantões para mostrar. */
        public bool $vinculado,
        public MesEscala $mes,
        public array $plantoesDoMes,
        public TabelaDeRemuneracao $tabela,
        public bool $ehFixo = false,
    ) {
    }

    /** O Fixo vê só a escala: o salário dele varia e não sai da tabela por plantão. */
    public function mostraValores(): bool
    {
        return !$this->ehFixo;
    }

    public static function semVinculo(MesEscala $mes, TabelaDeRemuneracao $tabela): self
    {
        return new self(false, $mes, [], $tabela);
    }

    public function totalDoMes(): Dinheiro
    {
        return self::somar($this->plantoesDoMes);
    }

    public function horasDoMes(): int
    {
        return array_sum(array_map(static fn (MeuPlantao $p): int => $p->horas(), $this->plantoesDoMes));
    }

    /** @return list<MeuPlantao> plantões do mês até hoje */
    public function realizados(): array
    {
        return array_values(array_filter($this->plantoesDoMes, static fn (MeuPlantao $p): bool => $p->realizado));
    }

    /** @return list<MeuPlantao> plantões do mês depois de hoje */
    public function aRealizar(): array
    {
        return array_values(array_filter($this->plantoesDoMes, static fn (MeuPlantao $p): bool => !$p->realizado));
    }

    public function valorRealizado(): Dinheiro
    {
        return self::somar($this->realizados());
    }

    public function valorARealizar(): Dinheiro
    {
        return self::somar($this->aRealizar());
    }

    /** @param list<MeuPlantao> $plantoes */
    private static function somar(array $plantoes): Dinheiro
    {
        return array_reduce($plantoes, static fn (Dinheiro $total, MeuPlantao $p): Dinheiro => $p->valor === null ? $total : $total->somar($p->valor), Dinheiro::zero());
    }
}
