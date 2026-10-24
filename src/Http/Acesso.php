<?php

declare(strict_types=1);

namespace Escala\Http;

enum Acesso
{
    case Publico;
    /** Qualquer usuário logado (portal do bombeiro). */
    case Logado;
    case Admin;
}
