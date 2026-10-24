<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

/**
 * Ciclo 24x72: dia 1 de serviço e dias 2–4 de folga. Hoje existe uma única escala de
 * Fixo; se surgir outra (ex.: 12x36), este é o ponto para extrair uma Strategy.
 */
final class CicloFixo
{
    public const DURACAO_EM_DIAS = 4;
    public const DIA_DE_SERVICO = 1;

    public function diaDoCiclo(ReferenciaCicloFixo $referencia, DataEscala $data): int
    {
        $diferenca = $referencia->data->diasAte($data);
        // o "+ DURACAO" garante resto positivo também para datas anteriores à referência
        $deslocamento = ($diferenca + $referencia->diaDoCiclo - 1) % self::DURACAO_EM_DIAS;
        return ($deslocamento + self::DURACAO_EM_DIAS) % self::DURACAO_EM_DIAS + 1;
    }

    public function estaDeServico(ReferenciaCicloFixo $referencia, DataEscala $data): bool
    {
        return $this->diaDoCiclo($referencia, $data) === self::DIA_DE_SERVICO;
    }
}
