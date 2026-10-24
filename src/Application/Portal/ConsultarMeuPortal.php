<?php

declare(strict_types=1);

namespace Escala\Application\Portal;

use DateTimeImmutable;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Escala\Plantao;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Escala\TabelaDeRemuneracao;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Usuario\UsuarioRepository;

/**
 * Página do bombeiro: só os plantões e valores dele. Plantões de colegas nunca são
 * carregados, para que nomes e valores de outras pessoas não cheguem à página.
 * Para o Fixo, os dias de serviço do ciclo 24x72 (menos as exceções) entram na escala
 * como integrais, como na planilha mensal, mas sem valor: o salário do Fixo varia.
 */
final class ConsultarMeuPortal
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly BombeiroRepository $bombeiros,
        private readonly PlantaoRepository $plantoes,
        private readonly ExcecaoFixoRepository $excecoes,
        private readonly CicloFixo $ciclo,
        private readonly TabelaDeRemuneracao $tabela,
    ) {
    }

    public function executar(int $usuarioId, MesEscala $mes, DataEscala $hoje): MeuPortal
    {
        $bombeiroId = $this->usuarios->buscarPorId($usuarioId)?->bombeiroId;
        $bombeiro = $bombeiroId === null ? null : $this->bombeiros->buscar($bombeiroId);
        if ($bombeiro === null) {
            return MeuPortal::semVinculo($mes, $this->tabela);
        }

        return new MeuPortal(
            vinculado: true,
            mes: $mes,
            plantoesDoMes: $this->meusDias($bombeiro, $mes->primeiroDia(), $mes->ultimoDia(), $hoje),
            tabela: $this->tabela,
            ehFixo: $bombeiro->ehFixo(),
        );
    }

    /**
     * Plantões registrados mais, para o Fixo, os dias do ciclo, em ordem de data.
     *
     * @return list<MeuPlantao>
     */
    private function meusDias(Bombeiro $bombeiro, DataEscala $inicio, DataEscala $fim, DataEscala $hoje): array
    {
        $id = (int) $bombeiro->id;
        $dias = array_map(
            fn (Plantao $p): MeuPlantao => $this->meuPlantao($bombeiro, $p->data, $p->turno, $hoje, doCiclo: false),
            $this->plantoes->doBombeiro($id, $inicio, $fim),
        );

        if ($bombeiro->ehFixo() && $bombeiro->referenciaCiclo !== null) {
            $integraisRegistrados = [];
            foreach ($dias as $dia) {
                if ($dia->horas() === 24) {
                    $integraisRegistrados[$dia->data->emTexto()] = true;
                }
            }
            $excecoes = $this->excecoes->doPeriodo($inicio, $fim);
            for ($data = $inicio; $data->emTexto() <= $fim->emTexto(); $data = $this->diaSeguinte($data)) {
                if ($this->ciclo->estaDeServico($bombeiro->referenciaCiclo, $data)
                    && !$excecoes->possui($id, $data)
                    && !isset($integraisRegistrados[$data->emTexto()])) {
                    $dias[] = $this->meuPlantao($bombeiro, $data, Turno::Integral, $hoje, doCiclo: true);
                }
            }
            usort($dias, static fn (MeuPlantao $a, MeuPlantao $b): int => $a->data->emTexto() <=> $b->data->emTexto());
        }
        return $dias;
    }

    private function meuPlantao(Bombeiro $bombeiro, DataEscala $data, Turno $turno, DataEscala $hoje, bool $doCiclo): MeuPlantao
    {
        $valor = $bombeiro->ehFixo() ? null : $this->tabela->valorDe($turno);
        return new MeuPlantao($data, $turno, $valor, $data->emTexto() <= $hoje->emTexto(), $doCiclo);
    }

    private function diaSeguinte(DataEscala $data): DataEscala
    {
        return DataEscala::deTexto((new DateTimeImmutable($data->emTexto()))->modify('+1 day')->format('Y-m-d'));
    }
}
