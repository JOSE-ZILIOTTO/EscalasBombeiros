<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

final class CicloFixoInvalido extends DadoInvalido
{
    public function __construct(string $mensagem = 'Para tipo Fixo, Data de Referência e Dia do Ciclo (1 a 4) são obrigatórios.')
    {
        parent::__construct($mensagem);
    }
}
