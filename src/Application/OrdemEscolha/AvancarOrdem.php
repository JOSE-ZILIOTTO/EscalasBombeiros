<?php

declare(strict_types=1);

namespace Escala\Application\OrdemEscolha;

use Escala\Application\Transacao;
use Escala\Domain\Bombeiro\Bombeiro;

final class AvancarOrdem
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly CarregadorDaOrdem $carregador,
    ) {
    }

    /** @return Bombeiro|null o novo bombeiro da vez; null quando não há BCs ativos */
    public function executar(): ?Bombeiro
    {
        return $this->transacao->executar(function (): ?Bombeiro {
            $carregada = $this->carregador->carregar();
            $avancada = $carregada->ordem->avancada();
            $this->carregador->salvar($avancada);

            return $carregada->bombeiro($avancada->estado->daVez);
        });
    }
}
