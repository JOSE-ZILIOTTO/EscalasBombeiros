<?php

declare(strict_types=1);

namespace Escala\Application\Auth;

use Escala\Domain\Excecao\RegraDeNegocioViolada;

final class MuitasTentativasDeLogin extends RegraDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('Muitas tentativas de login. Aguarde alguns minutos e tente novamente.');
    }
}
