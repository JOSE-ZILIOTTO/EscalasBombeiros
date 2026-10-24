<?php

declare(strict_types=1);

namespace Escala\Domain\Usuario;

use Escala\Domain\Excecao\BombeiroJaVinculado;

interface UsuarioRepository
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorLogin(string $login): ?Usuario;

    /** @return list<Usuario> por nome */
    public function todos(): array;

    /**
     * @param bool $bloquearParaEscrita trava as contas de admin até o fim da transação, para que
     *                                  dois admins não se desativem ao mesmo tempo e zerem o total
     */
    public function contarAdminsAtivos(bool $bloquearParaEscrita = false): int;

    /** @throws BombeiroJaVinculado */
    public function inserir(Usuario $usuario): int;

    /** @throws BombeiroJaVinculado */
    public function atualizar(Usuario $usuario): void;
}
