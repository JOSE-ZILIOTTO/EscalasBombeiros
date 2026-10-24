<?php

declare(strict_types=1);

namespace Escala\Application\Auth;

/** Armazenamento por visitante; a implementação real usa a sessão nativa do PHP. */
interface Sessao
{
    public function obter(string $chave): mixed;

    public function definir(string $chave, mixed $valor): void;

    public function remover(string $chave): void;

    /** Troca o ID da sessão mantendo os dados (evita fixação de sessão no login). */
    public function regenerarId(): void;

    public function encerrar(): void;
}
