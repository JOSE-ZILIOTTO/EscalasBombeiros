<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

use RuntimeException;

/**
 * Base das exceções de regra e validação. A mensagem é sempre segura para exibir
 * ao usuário; falhas técnicas (banco, I/O) nunca usam esta hierarquia.
 */
abstract class ExcecaoDeDominio extends RuntimeException
{
}
