<?php

declare(strict_types=1);

namespace Escala\Domain\Planilha;

interface AssinaturaRepository
{
    /** A assinatura salva, ou a padrão se nenhuma foi salva ainda. */
    public function obter(): AssinaturaDaPlanilha;

    public function salvar(AssinaturaDaPlanilha $assinatura): void;
}
