<?php

declare(strict_types=1);

namespace Escala\Http\View;

use RuntimeException;
use Throwable;

/** Templates em PHP puro; cada um recebe $v (AjudanteDeView) além dos dados da tela. */
final class RenderizadorPhp implements RenderizadorDeTemplate
{
    public function __construct(private readonly string $diretorio, private readonly AjudanteDeView $ajudante)
    {
    }

    public function renderizar(string $template, array $dados = []): string
    {
        // o nome vem só do código (controllers), mas recusar ../ evita incluir arquivo errado por engano
        if (preg_match('/^[a-z0-9_]+$/', $template) !== 1) {
            throw new RuntimeException("Nome de template inválido: {$template}");
        }
        $arquivo = $this->diretorio . DIRECTORY_SEPARATOR . $template . '.php';
        if (!is_file($arquivo)) {
            throw new RuntimeException("Template não encontrado: {$template}");
        }

        $renderizar = static function (string $__arquivo, array $__dados, AjudanteDeView $v): void {
            extract($__dados, EXTR_SKIP);
            require $__arquivo;
        };

        ob_start();
        try {
            $renderizar($arquivo, $dados, $this->ajudante);
            return (string) ob_get_clean();
        } catch (Throwable $erro) {
            ob_end_clean();
            throw $erro;
        }
    }
}
