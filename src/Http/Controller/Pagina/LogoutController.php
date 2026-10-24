<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Auth\Autenticador;
use Escala\Http\Manipulador;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;

final class LogoutController implements Manipulador
{
    public function __construct(private readonly Autenticador $autenticador)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $this->autenticador->sair();
        return new Redirecionamento('index.php');
    }
}
