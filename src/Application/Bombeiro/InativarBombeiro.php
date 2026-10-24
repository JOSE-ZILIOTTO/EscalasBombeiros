<?php

declare(strict_types=1);

namespace Escala\Application\Bombeiro;

use Escala\Application\OrdemEscolha\CarregadorDaOrdem;
use Escala\Application\Transacao;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Excecao\BombeiroNaoEncontrado;

/** Exclusão lógica: o histórico de plantões é preservado. */
final class InativarBombeiro
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly BombeiroRepository $bombeiros,
        private readonly CarregadorDaOrdem $ordem,
    ) {
    }

    public function executar(int $bombeiroId): void
    {
        $this->transacao->executar(function () use ($bombeiroId): void {
            $bombeiro = $this->bombeiros->buscar($bombeiroId) ?? throw new BombeiroNaoEncontrado();
            $this->bombeiros->atualizar($bombeiro->inativado());

            $carregada = $this->ordem->carregar();
            $this->ordem->salvar($carregada->ordem->semBombeiroNaVez($bombeiroId));
        });
    }
}
