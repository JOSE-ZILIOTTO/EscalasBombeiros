<?php

declare(strict_types=1);

namespace Escala\Http;

final readonly class Rota
{
    /**
     * @param class-string<Manipulador> $controlador
     * @param list<string>              $metodos
     */
    public function __construct(
        public string $controlador,
        public FormatoResposta $formato,
        public Acesso $acesso,
        public array $metodos = ['GET'],
        public bool $verificaCsrf = true,
    ) {
    }
}
