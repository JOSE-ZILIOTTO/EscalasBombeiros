<?php

declare(strict_types=1);

namespace Escala\Domain\Bombeiro;

use Escala\Domain\Escala\DataEscala;

/**
 * Quanto tempo os dados pessoais de quem saiu continuam guardados. Depois do prazo
 * contado da inativação, eles devem ser apagados (anonimizados).
 */
final readonly class PoliticaDeRetencao
{
    public function __construct(public int $anos)
    {
    }

    public function venceu(Bombeiro $bombeiro, DataEscala $hoje): bool
    {
        if ($bombeiro->ativo || !$bombeiro->temDadosPessoais() || $bombeiro->inativadoEm === null) {
            return false;
        }
        $limite = (new \DateTimeImmutable($bombeiro->inativadoEm->emTexto()))->modify("+{$this->anos} years");
        return $hoje->emTexto() >= $limite->format('Y-m-d');
    }
}
