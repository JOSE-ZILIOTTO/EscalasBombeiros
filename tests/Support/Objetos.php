<?php

declare(strict_types=1);

namespace Escala\Tests\Support;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\Plantao;
use Escala\Domain\Escala\ReferenciaCicloFixo;
use Escala\Domain\Escala\Turno;

/** Object Mother: cria objetos de domínio válidos com o mínimo de ruído nos testes. */
final class Objetos
{
    public static function bc(int $id, string $nome = 'BC Teste', bool $ativo = true): Bombeiro
    {
        return new Bombeiro($id, $nome, null, TipoBombeiro::Bc, $ativo, null);
    }

    /** Fixo de serviço em $diaDeServico e a cada 4 dias a partir dele. */
    public static function fixo(int $id, string $diaDeServico, string $nome = 'Fixo Teste'): Bombeiro
    {
        return new Bombeiro($id, $nome, null, TipoBombeiro::Fixo, true, new ReferenciaCicloFixo(DataEscala::deTexto($diaDeServico), 1));
    }

    public static function plantao(Turno $turno, TipoBombeiro $tipo = TipoBombeiro::Bc, int $bombeiroId = 1, string $data = '2025-11-03', int $id = 1): Plantao
    {
        return new Plantao($id, $bombeiroId, "Bombeiro {$bombeiroId}", $tipo, DataEscala::deTexto($data), $turno);
    }
}
