<?php

declare(strict_types=1);

namespace Escala\Domain\OrdemEscolha;

use Escala\Domain\Excecao\BombeiroNaoEBcAtivo;

/**
 * Rodízio dos BCs. Imutável: cada operação devolve uma nova ordem, e quem chama decide
 * se persiste. Assim a leitura do painel nunca grava nada no banco.
 */
final readonly class OrdemDeEscolha
{
    /** @param list<int> $bcsAtivos ids em ordem crescente */
    public function __construct(private array $bcsAtivos, public EstadoOrdemEscolha $estado)
    {
    }

    /** @return list<int> começa logo após quem iniciou a seleção atual */
    public function sequencia(): array
    {
        $indice = $this->indiceDe($this->estado->ultimoQueIniciou);
        if ($indice === null) {
            return $this->bcsAtivos;
        }
        $inicio = ($indice + 1) % count($this->bcsAtivos);
        return [...array_slice($this->bcsAtivos, $inicio), ...array_slice($this->bcsAtivos, 0, $inicio)];
    }

    public function primeiro(): ?int
    {
        return $this->sequencia()[0] ?? null;
    }

    /** Quem está na vez; sem ninguém válido na vez, o sugerido é o primeiro da sequência. */
    public function sugerido(): ?int
    {
        return $this->daVezValido() ?? $this->primeiro();
    }

    /**
     * Sem ninguém válido na vez, a seleção começa: o primeiro da sequência passa a ser
     * "da vez" e "quem iniciou". É a mesma inicialização que o sistema antigo fazia ao abrir o painel.
     */
    public function iniciada(): self
    {
        if ($this->daVezValido() !== null) {
            return $this;
        }
        $primeiro = $this->primeiro();
        return new self($this->bcsAtivos, new EstadoOrdemEscolha($primeiro, $primeiro, $this->estado->inicioSelecionado));
    }

    public function avancada(): self
    {
        $ordem = $this->iniciada();
        $sequencia = $ordem->sequencia();
        if ($sequencia === []) {
            return $ordem->comDaVez(null);
        }
        $atual = array_search($ordem->estado->daVez, $sequencia, true);
        $proximo = $atual === false ? $sequencia[0] : $sequencia[($atual + 1) % count($sequencia)];
        return $ordem->comDaVez($proximo);
    }

    /** O admin escolhe quem "iniciaria" o mês; o da vez passa a ser o seguinte a ele. */
    public function comInicio(int $bombeiroId): self
    {
        $sequencia = $this->sequencia();
        $indice = array_search($bombeiroId, $sequencia, true);
        if ($indice === false) {
            throw new BombeiroNaoEBcAtivo();
        }
        $proximo = $sequencia[($indice + 1) % count($sequencia)];
        return new self($this->bcsAtivos, new EstadoOrdemEscolha($this->estado->ultimoQueIniciou, $proximo, $bombeiroId));
    }

    /** Usado quando um bombeiro é inativado: se era o da vez, a vez fica livre. */
    public function semBombeiroNaVez(int $bombeiroId): self
    {
        return $this->estado->daVez === $bombeiroId ? $this->comDaVez(null) : $this;
    }

    private function daVezValido(): ?int
    {
        return $this->indiceDe($this->estado->daVez) === null ? null : $this->estado->daVez;
    }

    private function comDaVez(?int $daVez): self
    {
        return new self($this->bcsAtivos, new EstadoOrdemEscolha($this->estado->ultimoQueIniciou, $daVez, $this->estado->inicioSelecionado));
    }

    private function indiceDe(?int $bombeiroId): ?int
    {
        if ($bombeiroId === null) {
            return null;
        }
        $indice = array_search($bombeiroId, $this->bcsAtivos, true);
        return $indice === false ? null : $indice;
    }
}
