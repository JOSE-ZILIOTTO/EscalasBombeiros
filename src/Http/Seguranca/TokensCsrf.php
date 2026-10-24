<?php

declare(strict_types=1);

namespace Escala\Http\Seguranca;

use Escala\Application\Auth\Sessao;

final class TokensCsrf
{
    private const CHAVE = 'csrf_token';

    public function __construct(private readonly Sessao $sessao)
    {
    }

    public function token(): string
    {
        $token = $this->sessao->obter(self::CHAVE);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->sessao->definir(self::CHAVE, $token);
        }
        return $token;
    }

    public function valido(?string $enviado): bool
    {
        $esperado = $this->sessao->obter(self::CHAVE);
        return is_string($esperado) && $esperado !== '' && is_string($enviado) && hash_equals($esperado, $enviado);
    }

    /** Após o login um novo token é emitido, para que um token anterior ao login não sirva mais. */
    public function renovar(): void
    {
        $this->sessao->remover(self::CHAVE);
    }
}
