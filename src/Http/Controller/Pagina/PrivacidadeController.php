<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\View\RenderizadorDeTemplate;

/** privacidade.php — aviso de privacidade (LGPD art. 9), público: vale também antes do login. */
final class PrivacidadeController implements Manipulador
{
    public function __construct(
        private readonly RenderizadorDeTemplate $templates,
        private readonly string $controlador,
        private readonly ?string $contato,
        private readonly int $anosDeRetencao,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        return new RespostaHtml($this->templates->renderizar('privacidade', [
            'controlador' => $this->controlador,
            'contato' => $this->contato,
            'anosDeRetencao' => $this->anosDeRetencao,
        ]));
    }
}
