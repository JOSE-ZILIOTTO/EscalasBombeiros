<?php

declare(strict_types=1);

namespace Escala\Application\Bombeiro;

use Escala\Application\Relogio;
use Escala\Application\Transacao;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\PoliticaDeRetencao;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Excecao\AnonimizacaoNaoPermitida;
use Escala\Domain\Excecao\BombeiroNaoEncontrado;

/**
 * Direito de eliminação (LGPD art. 18, VI) e fim do prazo de retenção: apaga CPF,
 * endereço, contatos, banco e uniforme de quem saiu. Nome e plantões ficam no histórico.
 */
final class AnonimizarBombeiros
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly BombeiroRepository $bombeiros,
        private readonly PoliticaDeRetencao $retencao,
        private readonly Relogio $relogio,
    ) {
    }

    /** @throws AnonimizacaoNaoPermitida|BombeiroNaoEncontrado */
    public function executar(int $bombeiroId): void
    {
        $this->transacao->executar(function () use ($bombeiroId): void {
            $bombeiro = $this->bombeiros->buscar($bombeiroId) ?? throw new BombeiroNaoEncontrado();
            $this->bombeiros->atualizar($bombeiro->anonimizado());
        });
    }

    /** @return list<Bombeiro> inativos cujo prazo de retenção já passou e que ainda têm dados */
    public function vencidos(): array
    {
        $hoje = $this->hoje();
        return array_values(array_filter(
            $this->bombeiros->todos(),
            fn (Bombeiro $b): bool => $this->retencao->venceu($b, $hoje),
        ));
    }

    /** @return int quantos foram anonimizados */
    public function executarVencidos(): int
    {
        return $this->transacao->executar(function (): int {
            $vencidos = $this->vencidos();
            foreach ($vencidos as $bombeiro) {
                $this->bombeiros->atualizar($bombeiro->anonimizado());
            }
            return count($vencidos);
        });
    }

    private function hoje(): DataEscala
    {
        return DataEscala::deTexto($this->relogio->agora()->format('Y-m-d'));
    }
}
