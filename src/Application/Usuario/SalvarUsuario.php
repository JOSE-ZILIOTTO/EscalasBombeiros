<?php

declare(strict_types=1);

namespace Escala\Application\Usuario;

use Escala\Application\Transacao;
use Escala\Domain\Excecao\CampoObrigatorio;
use Escala\Domain\Excecao\SenhaFraca;
use Escala\Domain\Excecao\UsuarioJaExiste;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Domain\Usuario\ProtecaoUltimoAdministrador;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;

final class SalvarUsuario
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly UsuarioRepository $usuarios,
        private readonly ProtecaoUltimoAdministrador $protecao,
        private readonly int $tamanhoMinimoSenha = 8,
    ) {
    }

    public function executar(SalvarUsuarioEntrada $entrada): ResultadoSalvarUsuario
    {
        $nome = trim($entrada->nome);
        $login = trim($entrada->login);
        if ($nome === '' || $login === '' || $entrada->senha === '') {
            throw new CampoObrigatorio('Preencha nome, usuário e senha.');
        }
        $papel = PapelUsuario::deCodigo($entrada->papel);
        if (mb_strlen($entrada->senha) < $this->tamanhoMinimoSenha) {
            throw new SenhaFraca("A senha deve ter pelo menos {$this->tamanhoMinimoSenha} caracteres.");
        }

        // hash fora da transação: é lento de propósito e não precisa segurar as travas do banco
        $senhaHash = password_hash($entrada->senha, PASSWORD_DEFAULT);

        return $this->transacao->executar(function () use ($entrada, $nome, $login, $papel, $senhaHash): ResultadoSalvarUsuario {
            $existente = $this->usuarios->buscarPorLogin($login);
            if ($existente !== null && !$entrada->sobrescrever) {
                throw new UsuarioJaExiste($login);
            }

            $usuario = new Usuario(
                id: $existente?->id,
                nome: $nome,
                login: $login,
                senhaHash: $senhaHash,
                papel: $papel,
                ativo: $entrada->ativo,
                bombeiroId: $entrada->bombeiroId !== null && $entrada->bombeiroId > 0 ? $entrada->bombeiroId : null,
            );

            if ($existente === null) {
                $this->usuarios->inserir($usuario);
                return ResultadoSalvarUsuario::Criado;
            }
            // substituir pode rebaixar ou desativar um admin: mesma proteção da lista de usuários
            $this->protecao->verificar($existente, $usuario, $this->usuarios->contarAdminsAtivos(bloquearParaEscrita: true));
            $this->usuarios->atualizar($usuario);
            return ResultadoSalvarUsuario::Atualizado;
        });
    }
}
