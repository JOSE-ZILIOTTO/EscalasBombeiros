<?php

declare(strict_types=1);

namespace Escala\Application\OrdemEscolha;

/** Somente leitura: não inicializa nem grava a ordem (o sistema antigo gravava ao abrir o painel). */
final class ConsultarOrdemDeEscolha
{
    public function __construct(private readonly CarregadorDaOrdem $carregador)
    {
    }

    public function executar(): ResumoDaOrdem
    {
        $carregada = $this->carregador->carregar();
        $ordem = $carregada->ordem;

        return new ResumoDaOrdem(
            $carregada->sequencia(),
            $carregada->bombeiro($ordem->primeiro()),
            $carregada->bombeiro($ordem->sugerido()),
            $ordem->estado->inicioSelecionado,
        );
    }
}
