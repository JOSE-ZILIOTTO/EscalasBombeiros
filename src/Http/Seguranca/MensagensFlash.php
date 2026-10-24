<?php

declare(strict_types=1);

namespace Escala\Http\Seguranca;

use Escala\Application\Auth\Sessao;

/** Mensagem exibida uma única vez na próxima página (padrão Post/Redirect/Get). */
final class MensagensFlash
{
    private const CHAVE = 'flash_message';

    public function __construct(private readonly Sessao $sessao)
    {
    }

    public function sucesso(string $mensagem): void
    {
        $this->sessao->definir(self::CHAVE, ['tipo' => 'success', 'mensagem' => $mensagem]);
    }

    public function erro(string $mensagem): void
    {
        $this->sessao->definir(self::CHAVE, ['tipo' => 'error', 'mensagem' => $mensagem]);
    }

    /** @return array{tipo: string, mensagem: string}|null */
    public function consumir(): ?array
    {
        $flash = $this->sessao->obter(self::CHAVE);
        $this->sessao->remover(self::CHAVE);
        return is_array($flash) && isset($flash['tipo'], $flash['mensagem'])
            ? ['tipo' => (string) $flash['tipo'], 'mensagem' => (string) $flash['mensagem']]
            : null;
    }
}
