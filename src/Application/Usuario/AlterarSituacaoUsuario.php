<?php

declare(strict_types=1);

namespace Escala\Application\Usuario;

use Escala\Application\Transacao;
use Escala\Domain\Excecao\UltimoAdministrador;
use Escala\Domain\Excecao\UsuarioNaoEncontrado;
use Escala\Domain\Usuario\ProtecaoUltimoAdministrador;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;

/** Ativa ou desativa um login. Desativar não apaga nada: o usuário só deixa de conseguir entrar. */
final class AlterarSituacaoUsuario
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly UsuarioRepository $usuarios,
        private readonly ProtecaoUltimoAdministrador $protecao,
    ) {
    }

    /**
     * @return Usuario o usuário com a nova situação
     * @throws UltimoAdministrador
     */
    public function executar(int $usuarioId, bool $ativo): Usuario
    {
        return $this->transacao->executar(function () use ($usuarioId, $ativo): Usuario {
            $atual = $this->usuarios->buscarPorId($usuarioId) ?? throw new UsuarioNaoEncontrado();
            $alterado = $atual->comSituacao($ativo);

            $this->protecao->verificar($atual, $alterado, $this->usuarios->contarAdminsAtivos(bloquearParaEscrita: true));
            $this->usuarios->atualizar($alterado);

            return $alterado;
        });
    }
}
