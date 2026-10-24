<?php

declare(strict_types=1);

namespace Escala\Http\View;

interface RenderizadorDeTemplate
{
    /** @param array<string, mixed> $dados */
    public function renderizar(string $template, array $dados = []): string;
}
