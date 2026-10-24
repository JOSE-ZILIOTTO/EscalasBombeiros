<?php

declare(strict_types=1);

namespace Escala\Http;

use Escala\Domain\Usuario\PapelUsuario;
use Escala\Http\Middleware\ExigirAutenticacao;
use Escala\Http\Middleware\ExigirMetodo;
use Escala\Http\Middleware\TratarErros;
use Escala\Http\Middleware\VerificarCsrf;
use Escala\Infrastructure\ContainerDaAplicacao;

/** Kernel HTTP: monta o pipeline de Decorators da rota e envia a resposta. */
final class Aplicacao
{
    public function __construct(
        private readonly ContainerDaAplicacao $container,
        private readonly TabelaDeRotas $rotas,
    ) {
    }

    public function executar(string $nomeDaRota): void
    {
        $this->tratar($nomeDaRota, Requisicao::atual())->enviar();
    }

    public function tratar(string $nomeDaRota, Requisicao $requisicao): Resposta
    {
        return $this->pipeline($this->rotas->rota($nomeDaRota))->tratar($requisicao);
    }

    /**
     * De fora para dentro: erros → método HTTP → autenticação → CSRF → controller.
     * A autenticação vem antes do CSRF para que um visitante sem login receba 401, não 403.
     */
    private function pipeline(Rota $rota): Manipulador
    {
        $c = $this->container;
        $manipulador = $c->controlador($rota->controlador);

        if ($rota->verificaCsrf) {
            $manipulador = new VerificarCsrf($manipulador, $c->csrf(), $rota->formato, $c->templates());
        }
        if ($rota->acesso !== Acesso::Publico) {
            $papel = $rota->acesso === Acesso::Admin ? PapelUsuario::Admin : null;
            $manipulador = new ExigirAutenticacao($manipulador, $c->autenticador(), $rota->formato, $papel);
        }
        $manipulador = new ExigirMetodo($manipulador, $rota->metodos, $rota->formato);

        return new TratarErros($manipulador, $c->logger(), $rota->formato, $c->templates());
    }
}
