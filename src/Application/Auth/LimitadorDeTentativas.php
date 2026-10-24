<?php

declare(strict_types=1);

namespace Escala\Application\Auth;

/** Protege o login contra força bruta; a chave identifica o cliente (ex.: IP). */
interface LimitadorDeTentativas
{
    public function estaBloqueado(string $chave): bool;

    public function registrarFalha(string $chave): void;

    public function limpar(string $chave): void;
}
