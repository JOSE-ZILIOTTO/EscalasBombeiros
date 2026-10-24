<?php

declare(strict_types=1);

namespace Escala\Http;

/** Define como um middleware recusa a requisição: JSON para a API, redirecionamento/HTML para páginas. */
enum FormatoResposta
{
    case Json;
    case Html;
}
