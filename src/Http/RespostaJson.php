<?php

declare(strict_types=1);

namespace Escala\Http;

final readonly class RespostaJson implements Resposta
{
    /** @param array<string, mixed> $dados */
    public function __construct(public array $dados, private int $status = 200)
    {
    }

    /** @param array<string, mixed> $extras */
    public static function sucesso(?string $mensagem = null, array $extras = []): self
    {
        return new self(['success' => true, ...($mensagem === null ? [] : ['message' => $mensagem]), ...$extras]);
    }

    /** Erro em formato compatível com os dois clientes JS: script.js lê message, portal lê erro. */
    public static function erro(string $mensagem, int $status): self
    {
        return new self(['success' => false, 'ok' => false, 'message' => $mensagem, 'erro' => $mensagem], $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
