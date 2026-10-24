<?php

declare(strict_types=1);

namespace Escala\Domain\Usuario;

/** Lembra quais administradores já viram (ou pularam) o tutorial do painel. */
interface TutorialRepository
{
    public function foiVisto(int $usuarioId): bool;

    public function marcarComoVisto(int $usuarioId): void;
}
