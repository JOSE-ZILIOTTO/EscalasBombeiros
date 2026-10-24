<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Escala\MontarCalendarioMes;
use Escala\Application\Relogio;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Usuario\TutorialRepository;
use Escala\Domain\Usuario\Usuario;
use Escala\Http\Manipulador;
use Escala\Http\Middleware\ExigirAutenticacao;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\View\RenderizadorDeTemplate;

/** escala.php — painel do administrador. */
final class EscalaController implements Manipulador
{
    public function __construct(
        private readonly MontarCalendarioMes $montarCalendario,
        private readonly TutorialRepository $tutorial,
        private readonly Relogio $relogio,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        /** @var Usuario $usuario */
        $usuario = $requisicao->atributo(ExigirAutenticacao::ATRIBUTO_USUARIO);
        $agora = $this->relogio->agora();
        $mes = MesEscala::deParametros($requisicao->consulta('year'), $requisicao->consulta('month'), $agora);
        return new RespostaHtml($this->templates->renderizar('escala', [
            'calendario' => $this->montarCalendario->executar($mes),
            'hoje' => $agora->format('Y-m-d'),
            // abre sozinho no primeiro acesso; ?tutorial=1 (botão de ajuda) reabre quando quiser
            'abrirTutorial' => $requisicao->consulta('tutorial') !== null || !$this->tutorial->foiVisto((int) $usuario->id),
        ]));
    }
}
