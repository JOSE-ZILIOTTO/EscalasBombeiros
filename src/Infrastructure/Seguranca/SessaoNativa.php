<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Seguranca;

use Escala\Application\Auth\Sessao;

/** Adapter da sessão nativa do PHP; inicia sob demanda com cookie seguro. */
final class SessaoNativa implements Sessao
{
    public function __construct(private readonly bool $conexaoSegura)
    {
    }

    public function obter(string $chave): mixed
    {
        $this->iniciar();
        return $_SESSION[$chave] ?? null;
    }

    public function definir(string $chave, mixed $valor): void
    {
        $this->iniciar();
        $_SESSION[$chave] = $valor;
    }

    public function remover(string $chave): void
    {
        $this->iniciar();
        unset($_SESSION[$chave]);
    }

    public function regenerarId(): void
    {
        $this->iniciar();
        session_regenerate_id(true);
    }

    public function encerrar(): void
    {
        $this->iniciar();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $p['path'],
                'domain' => $p['domain'],
                'secure' => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }
        session_destroy();
    }

    private function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        ini_set('session.use_strict_mode', '1'); // recusa IDs de sessão não emitidos pelo servidor
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->conexaoSegura,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
