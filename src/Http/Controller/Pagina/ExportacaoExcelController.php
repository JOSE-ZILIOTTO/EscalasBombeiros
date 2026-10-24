<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Exportacao\GerarPlanilhaMensal;
use Escala\Application\Relogio;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Planilha\AssinaturaRepository;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaArquivo;
use Escala\Infrastructure\Exportacao\PlanilhaXlsx;

/** exportar_excel.php?month=&year= — a mesma planilha mensal, em .xlsx. */
final class ExportacaoExcelController implements Manipulador
{
    public function __construct(
        private readonly GerarPlanilhaMensal $gerarPlanilha,
        private readonly AssinaturaRepository $assinaturas,
        private readonly PlanilhaXlsx $xlsx,
        private readonly Relogio $relogio,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $mes = MesEscala::deParametros($requisicao->consulta('year'), $requisicao->consulta('month'), $this->relogio->agora());
        $planilha = $this->gerarPlanilha->executar($mes);

        return new RespostaArquivo(
            $this->xlsx->gerar($planilha, $this->assinaturas->obter()),
            $this->xlsx->nomeDoArquivo($planilha),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
    }
}
