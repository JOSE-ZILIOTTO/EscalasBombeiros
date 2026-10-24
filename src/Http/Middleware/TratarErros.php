<?php

declare(strict_types=1);

namespace Escala\Http\Middleware;

use Escala\Domain\Excecao\DadoInvalido;
use Escala\Domain\Excecao\ExcecaoDeDominio;
use Escala\Domain\Excecao\RecursoNaoEncontrado;
use Escala\Domain\Excecao\RegraDeNegocioViolada;
use Escala\Http\FormatoResposta;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\RespostaJson;
use Escala\Http\View\RenderizadorDeTemplate;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Camada mais externa: traduz exceções em respostas. Mensagens de domínio vão para o
 * usuário; qualquer outra falha é logada e vira uma mensagem genérica (sem detalhes internos).
 */
final class TratarErros implements Manipulador
{
    public function __construct(
        private readonly Manipulador $interno,
        private readonly LoggerInterface $logger,
        private readonly FormatoResposta $formato,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        try {
            return $this->interno->tratar($requisicao);
        } catch (ExcecaoDeDominio $erro) {
            return $this->responder($erro->getMessage(), self::statusDe($erro));
        } catch (Throwable $erro) {
            $this->logger->error('Erro não tratado: {mensagem}', ['mensagem' => $erro->getMessage(), 'exception' => $erro]);
            return $this->responder('Erro interno no servidor. Tente novamente.', 500);
        }
    }

    /** A hierarquia de exceções decide o status: uma exceção nova herda o status certo sem mexer aqui. */
    public static function statusDe(ExcecaoDeDominio $erro): int
    {
        return match (true) {
            $erro instanceof RecursoNaoEncontrado => 404,
            $erro instanceof RegraDeNegocioViolada => 409,
            $erro instanceof DadoInvalido => 400,
            default => 400,
        };
    }

    private function responder(string $mensagem, int $status): Resposta
    {
        if ($this->formato === FormatoResposta::Json) {
            return RespostaJson::erro($mensagem, $status);
        }
        try {
            return new RespostaHtml($this->templates->renderizar('erro', ['mensagem' => $mensagem]), $status);
        } catch (Throwable) {
            return new RespostaHtml(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'), $status);
        }
    }
}
