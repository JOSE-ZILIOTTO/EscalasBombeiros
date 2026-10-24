<?php

declare(strict_types=1);

namespace Escala\Application\Escala;

use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Excecao\BombeiroNaoEFixo;
use Escala\Domain\Excecao\CampoObrigatorio;
use Escala\Domain\Excecao\FixoForaDeServico;

/** Marca que o Fixo NÃO comparecerá no seu dia de ciclo (libera a vaga de substituto). */
final class RegistrarExcecaoFixo
{
    public function __construct(
        private readonly BombeiroRepository $bombeiros,
        private readonly ExcecaoFixoRepository $excecoes,
        private readonly CicloFixo $ciclo,
    ) {
    }

    public function executar(int $bombeiroId, string $data): void
    {
        if ($bombeiroId <= 0 || trim($data) === '') {
            throw new CampoObrigatorio('Parâmetros ausentes.');
        }
        $dia = DataEscala::deTexto($data);

        $bombeiro = $this->bombeiros->buscar($bombeiroId);
        if ($bombeiro === null || !$bombeiro->ativo || !$bombeiro->ehFixo() || $bombeiro->referenciaCiclo === null) {
            throw new BombeiroNaoEFixo();
        }
        if (!$this->ciclo->estaDeServico($bombeiro->referenciaCiclo, $dia)) {
            throw new FixoForaDeServico();
        }
        $this->excecoes->registrar($bombeiroId, $dia);
    }
}
