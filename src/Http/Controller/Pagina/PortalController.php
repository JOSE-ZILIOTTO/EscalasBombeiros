<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Portal\ConsultarMeuPortal;
use Escala\Application\Relogio;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Usuario\Usuario;
use Escala\Http\Manipulador;
use Escala\Http\Middleware\ExigirAutenticacao;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\View\RenderizadorDeTemplate;

/** portal_usuario.php — resumo de valores e plantões do bombeiro logado. */
final class PortalController implements Manipulador
{
    public function __construct(
        private readonly ConsultarMeuPortal $consultar,
        private readonly Relogio $relogio,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        /** @var Usuario $usuario */
        $usuario = $requisicao->atributo(ExigirAutenticacao::ATRIBUTO_USUARIO);
        $agora = $this->relogio->agora();
        $hoje = DataEscala::deTexto($agora->format('Y-m-d'));
        $mes = MesEscala::deParametros($requisicao->consulta('year'), $requisicao->consulta('month'), $agora);

        return new RespostaHtml($this->templates->renderizar('portal', [
            'usuario' => $usuario,
            'portal' => $this->consultar->executar((int) $usuario->id, $mes, $hoje),
            'hoje' => $hoje,
            'mesAtual' => MesEscala::de((int) $agora->format('Y'), (int) $agora->format('n')),
        ]));
    }
}
