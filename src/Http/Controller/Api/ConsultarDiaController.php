<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Api;

use Escala\Application\Escala\ConsultarDia;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Escala\Plantao;
use Escala\Domain\Excecao\CampoObrigatorio;
use Escala\Http\Manipulador;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaJson;

/** GET api/api_get_details.php?date=Y-m-d — dados do modal do dia. */
final class ConsultarDiaController implements Manipulador
{
    public function __construct(private readonly ConsultarDia $consultarDia)
    {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $data = $requisicao->consulta('date') ?? throw new CampoObrigatorio('Data não fornecida.');
        $detalhes = $this->consultarDia->executar($data);
        $quadro = $detalhes->quadro;
        $fixo = $quadro->fixo;

        return RespostaJson::sucesso(extras: [
            'fixo_calculado' => $fixo === null ? null : [
                'id' => $fixo->bombeiro->id,
                'nome_completo' => $fixo->bombeiro->nomeCompleto,
                'fixo_ref_data' => $fixo->bombeiro->referenciaCiclo?->data->emTexto(),
                'fixo_ref_dia_ciclo' => $fixo->bombeiro->referenciaCiclo?->diaDoCiclo,
                'tem_excecao' => $fixo->temExcecao,
            ],
            'extras' => array_map(static fn (Plantao $p): array => [
                'plantao_id' => $p->id,
                'turno' => $p->turno->value,
                'bombeiro_id' => $p->bombeiroId,
                'nome_completo' => $p->nomeBombeiro,
                'tipo' => $p->tipoBombeiro->value,
            ], $quadro->plantoes),
            'vagas' => [
                'D' => $quadro->vagas->diurno,
                'N' => $quadro->vagas->noturno,
                'I_SUB' => $quadro->vagas->substituto,
            ],
            'bombeiros_ativos' => array_map(static fn (Bombeiro $b): array => [
                'id' => $b->id,
                'nome_completo' => $b->nomeCompleto,
                'tipo' => $b->tipo->value,
            ], $detalhes->bombeirosAtivos),
            'proximo_sugerido' => $detalhes->sugerido === null ? null : [
                'id' => $detalhes->sugerido->id,
                'nome' => $detalhes->sugerido->nomeCompleto,
            ],
        ]);
    }
}
