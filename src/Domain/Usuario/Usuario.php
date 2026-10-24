<?php

declare(strict_types=1);

namespace Escala\Domain\Usuario;

final readonly class Usuario
{
    public function __construct(
        public ?int $id,
        public string $nome,
        public string $login,
        public string $senhaHash,
        public PapelUsuario $papel,
        public bool $ativo,
        /** Bombeiro dono deste login; o portal usa para saber quais plantões são "meus". */
        public ?int $bombeiroId,
    ) {
    }

    public function ehAdmin(): bool
    {
        return $this->papel === PapelUsuario::Admin;
    }

    /** Só administradores ativos conseguem entrar no painel e gerenciar o sistema. */
    public function ehAdminAtivo(): bool
    {
        return $this->ativo && $this->ehAdmin();
    }

    public function comSituacao(bool $ativo): self
    {
        return new self($this->id, $this->nome, $this->login, $this->senhaHash, $this->papel, $ativo, $this->bombeiroId);
    }
}
