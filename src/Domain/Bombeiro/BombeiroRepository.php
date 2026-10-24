<?php

declare(strict_types=1);

namespace Escala\Domain\Bombeiro;

interface BombeiroRepository
{
    public function buscar(int $id): ?Bombeiro;

    /** @return list<Bombeiro> ativos e inativos, por nome */
    public function todos(): array;

    /** @return list<Bombeiro> por nome */
    public function ativos(): array;

    /** @return list<Bombeiro> por id — a ordem decide qual Fixo vale quando dois coincidem */
    public function fixosAtivos(): array;

    /** @return list<Bombeiro> por id — base da ordem de escolha */
    public function bcsAtivos(): array;

    /** @return int id gerado */
    public function inserir(Bombeiro $bombeiro): int;

    public function atualizar(Bombeiro $bombeiro): void;
}
