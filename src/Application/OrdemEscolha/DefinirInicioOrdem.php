<?php

declare(strict_types=1);

namespace Escala\Application\OrdemEscolha;

use Escala\Application\Transacao;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Excecao\BombeiroNaoEBcAtivo;

final class DefinirInicioOrdem
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly CarregadorDaOrdem $carregador,
    ) {
    }

    /**
     * @return Bombeiro|null o próximo sugerido após o início escolhido
     * @throws BombeiroNaoEBcAtivo
     */
    public function executar(int $bombeiroId): ?Bombeiro
    {
        if ($bombeiroId <= 0) {
            throw new BombeiroNaoEBcAtivo('ID inválido.');
        }
        return $this->transacao->executar(function () use ($bombeiroId): ?Bombeiro {
            $carregada = $this->carregador->carregar();
            $nova = $carregada->ordem->comInicio($bombeiroId);
            $this->carregador->salvar($nova);

            return $carregada->bombeiro($nova->estado->daVez);
        });
    }
}
