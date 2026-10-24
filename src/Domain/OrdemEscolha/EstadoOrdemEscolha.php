<?php

declare(strict_types=1);

namespace Escala\Domain\OrdemEscolha;

/** O que é persistido da ordem de escolha (tabela configuracoes). */
final readonly class EstadoOrdemEscolha
{
    public function __construct(
        /** BC que iniciou a seleção atual; a sequência começa logo depois dele. */
        public ?int $ultimoQueIniciou = null,
        public ?int $daVez = null,
        /** Escolha manual do admin no seletor "Ordem deste mês iniciaria com". */
        public ?int $inicioSelecionado = null,
    ) {
    }
}
