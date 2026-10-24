<?php

declare(strict_types=1);

namespace Escala\Application\Bombeiro;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Excecao\BombeiroNaoEncontrado;

final class ConsultarBombeiros
{
    public function __construct(private readonly BombeiroRepository $bombeiros)
    {
    }

    /** @return list<Bombeiro> */
    public function todos(): array
    {
        return $this->bombeiros->todos();
    }

    /** @return list<Bombeiro> */
    public function ativos(): array
    {
        return $this->bombeiros->ativos();
    }

    public function buscar(int $id): Bombeiro
    {
        return $this->bombeiros->buscar($id) ?? throw new BombeiroNaoEncontrado();
    }
}
