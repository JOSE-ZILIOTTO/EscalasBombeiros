<?php

declare(strict_types=1);

namespace Escala\Application\Auth;

use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;

final class Autenticador
{
    // nomes mantidos do sistema antigo: sessões abertas antes da atualização continuam válidas
    private const CHAVE_USUARIO = 'usuario_id';
    private const CHAVE_NOME = 'usuario_nome';
    private const CHAVE_PAPEL = 'usuario_tipo';

    /** Hash real de uma senha aleatória: login inexistente leva o mesmo tempo que senha errada. */
    private const HASH_FICTICIO = '$2y$10$nCP1RSZomf/3q4Mn9MukGeb48.ehbjd8DPjGBgoNH8X3KHR780ije';

    private ?Usuario $usuarioDaRequisicao = null;
    private bool $usuarioCarregado = false;

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly Sessao $sessao,
        private readonly LimitadorDeTentativas $limitador,
    ) {
    }

    /**
     * @throws MuitasTentativasDeLogin
     * @throws CredenciaisInvalidas
     */
    public function entrar(string $login, string $senha, string $chaveCliente): Usuario
    {
        if ($this->limitador->estaBloqueado($chaveCliente)) {
            throw new MuitasTentativasDeLogin();
        }

        $usuario = $this->usuarios->buscarPorLogin($login);
        $senhaConfere = password_verify($senha, $usuario?->senhaHash ?? self::HASH_FICTICIO);

        if ($usuario === null || !$usuario->ativo || !$senhaConfere || $usuario->id === null) {
            $this->limitador->registrarFalha($chaveCliente);
            throw new CredenciaisInvalidas();
        }

        $this->limitador->limpar($chaveCliente);
        $this->sessao->regenerarId();
        $this->sessao->definir(self::CHAVE_USUARIO, $usuario->id);
        $this->sessao->definir(self::CHAVE_NOME, $usuario->nome);
        $this->sessao->definir(self::CHAVE_PAPEL, $usuario->papel->value);
        $this->lembrar($usuario);

        return $usuario;
    }

    /**
     * Revalida no banco a cada requisição: um usuário desativado perde o acesso na hora,
     * sem esperar a sessão expirar.
     */
    public function usuarioAtual(): ?Usuario
    {
        if ($this->usuarioCarregado) {
            return $this->usuarioDaRequisicao;
        }

        $id = $this->sessao->obter(self::CHAVE_USUARIO);
        $usuario = is_int($id) || (is_string($id) && ctype_digit($id))
            ? $this->usuarios->buscarPorId((int) $id)
            : null;

        if ($id !== null && ($usuario === null || !$usuario->ativo)) {
            $this->sessao->encerrar();
            $usuario = null;
        }
        $this->lembrar($usuario);

        return $usuario;
    }

    public function sair(): void
    {
        $this->sessao->encerrar();
        $this->lembrar(null);
    }

    private function lembrar(?Usuario $usuario): void
    {
        $this->usuarioDaRequisicao = $usuario;
        $this->usuarioCarregado = true;
    }
}
