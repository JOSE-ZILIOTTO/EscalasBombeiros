<?php

declare(strict_types=1);

namespace Escala\Application\Portal;

use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Usuario\Usuario;

/**
 * Direitos de acesso e portabilidade (LGPD art. 18, II e V): tudo o que o sistema guarda
 * sobre quem está logado. Como no portal, nada de outras pessoas é carregado.
 */
final class ConsultarMeusDados
{
    public function __construct(
        private readonly BombeiroRepository $bombeiros,
        private readonly PlantaoRepository $plantoes,
    ) {
    }

    public function executar(Usuario $usuario): MeusDados
    {
        $bombeiro = $usuario->bombeiroId === null ? null : $this->bombeiros->buscar($usuario->bombeiroId);
        $plantoes = $bombeiro?->id === null ? [] : $this->plantoes->doBombeiro(
            $bombeiro->id,
            DataEscala::de(2000, 1, 1),
            DataEscala::de(2100, 12, 31),
        );

        return new MeusDados($usuario, $bombeiro, $plantoes);
    }
}
