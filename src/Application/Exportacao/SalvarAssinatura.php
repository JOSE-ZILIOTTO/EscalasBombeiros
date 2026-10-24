<?php

declare(strict_types=1);

namespace Escala\Application\Exportacao;

use Escala\Application\Transacao;
use Escala\Domain\Planilha\AssinaturaDaPlanilha;
use Escala\Domain\Planilha\AssinaturaRepository;

final class SalvarAssinatura
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly AssinaturaRepository $assinaturas,
    ) {
    }

    public function executar(string $nome, string $cargo, string $unidade): AssinaturaDaPlanilha
    {
        $assinatura = new AssinaturaDaPlanilha($nome, $cargo, $unidade);
        $this->transacao->executar(fn () => $this->assinaturas->salvar($assinatura));
        return $assinatura;
    }
}
