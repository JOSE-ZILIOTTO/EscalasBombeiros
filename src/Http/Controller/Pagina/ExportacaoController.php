<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Exportacao\FormatadorDeNomeDestaque;
use Escala\Application\Exportacao\GerarPlanilhaMensal;
use Escala\Application\Exportacao\SalvarAssinatura;
use Escala\Application\Relogio;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Excecao\ExcecaoDeDominio;
use Escala\Domain\Planilha\AssinaturaRepository;
use Escala\Http\Manipulador;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\Seguranca\MensagensFlash;
use Escala\Http\View\RenderizadorDeTemplate;

/** exportar_tabela_formatada.php — planilha mensal para impressão e edição da assinatura do rodapé. */
final class ExportacaoController implements Manipulador
{
    public function __construct(
        private readonly GerarPlanilhaMensal $gerarPlanilha,
        private readonly FormatadorDeNomeDestaque $formatador,
        private readonly AssinaturaRepository $assinaturas,
        private readonly SalvarAssinatura $salvarAssinatura,
        private readonly MensagensFlash $flash,
        private readonly Relogio $relogio,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        if ($requisicao->ehPost()) {
            return $this->salvarAssinatura($requisicao);
        }

        $flash = $this->flash->consumir();
        $mes = MesEscala::deParametros($requisicao->consulta('year'), $requisicao->consulta('month'), $this->relogio->agora());
        return new RespostaHtml($this->templates->renderizar('exportacao', [
            'planilha' => $this->gerarPlanilha->executar($mes),
            'formatador' => $this->formatador,
            'assinatura' => $this->assinaturas->obter(),
            'aviso' => $flash === null ? null : ['sucesso' => $flash['tipo'] === 'success', 'mensagem' => $flash['mensagem']],
            'hoje' => $this->relogio->agora()->format('Y-m-d'),
        ]));
    }

    private function salvarAssinatura(Requisicao $requisicao): Resposta
    {
        try {
            $this->salvarAssinatura->executar(
                $requisicao->campo('assinatura_nome') ?? '',
                $requisicao->campo('assinatura_cargo') ?? '',
                $requisicao->campo('assinatura_unidade') ?? '',
            );
            $this->flash->sucesso('Assinatura da planilha atualizada.');
        } catch (ExcecaoDeDominio $erro) {
            $this->flash->erro($erro->getMessage());
        }

        $mes = MesEscala::deParametros($requisicao->campo('year'), $requisicao->campo('month'), $this->relogio->agora());
        return new Redirecionamento("exportar_tabela_formatada.php?month={$mes->mes}&year={$mes->ano}");
    }
}
