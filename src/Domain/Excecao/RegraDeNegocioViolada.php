<?php

declare(strict_types=1);

namespace Escala\Domain\Excecao;

/** Entrada válida, mas a ação conflita com o estado atual (vaga ocupada, duplicidade...). */
abstract class RegraDeNegocioViolada extends ExcecaoDeDominio
{
}
