<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Application;

use Escala\Application\Exportacao\FormatadorDeNomeDestaque;
use Escala\Application\Exportacao\GerarPlanilhaMensal;
use Escala\Application\Portal\ConsultarMeuPortal;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\ExcecoesDoPeriodo;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Escala\Plantao;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Escala\TabelaDeRemuneracao;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Shared\Dinheiro;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;
use Escala\Tests\Support\Objetos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PortalEPlanilhaTest extends TestCase
{
    #[Test]
    public function portalSomaSoOsPlantoesDoMesExibidoEDoProprioBombeiro(): void
    {
        $plantoes = $this->plantoesEmMemoria([
            Objetos::plantao(Turno::Integral, bombeiroId: 7, data: '2025-10-31'), // outro mês
            Objetos::plantao(Turno::Integral, bombeiroId: 7, data: '2025-11-03'),
            Objetos::plantao(Turno::Diurno, bombeiroId: 7, data: '2025-11-20'),
            Objetos::plantao(Turno::Noturno, bombeiroId: 7, data: '2025-12-01'), // outro mês
            Objetos::plantao(Turno::Integral, bombeiroId: 8, data: '2025-11-03'), // colega
        ]);

        $portal = $this->portal($plantoes, bombeiroId: 7)->executar(1, MesEscala::de(2025, 11), DataEscala::deTexto('2025-11-10'));

        self::assertTrue($portal->vinculado);
        self::assertCount(2, $portal->plantoesDoMes, 'nem outros meses nem o colega entram');
        self::assertSame(375, $portal->totalDoMes()->emReais());
        self::assertSame(36, $portal->horasDoMes());
        self::assertSame(250, $portal->valorRealizado()->emReais(), '03/11 já passou');
        self::assertSame(125, $portal->valorARealizar()->emReais(), '20/11 ainda não aconteceu');
    }

    #[Test]
    public function aoNavegarParaOutroMesOsValoresSaoDaqueleMes(): void
    {
        $plantoes = $this->plantoesEmMemoria([
            Objetos::plantao(Turno::Integral, bombeiroId: 7, data: '2025-10-05'),
            Objetos::plantao(Turno::Diurno, bombeiroId: 7, data: '2025-12-15'),
        ]);
        $consulta = $this->portal($plantoes, bombeiroId: 7);
        $hoje = DataEscala::deTexto('2025-11-10');

        $outubro = $consulta->executar(1, MesEscala::de(2025, 10), $hoje);
        self::assertSame(250, $outubro->totalDoMes()->emReais());
        self::assertSame(250, $outubro->valorRealizado()->emReais());

        $novembro = $consulta->executar(1, MesEscala::de(2025, 11), $hoje);
        self::assertSame(0, $novembro->totalDoMes()->emReais());

        $dezembro = $consulta->executar(1, MesEscala::de(2025, 12), $hoje);
        self::assertSame(125, $dezembro->totalDoMes()->emReais());
        self::assertSame(125, $dezembro->valorARealizar()->emReais());
        self::assertSame(0, $dezembro->valorRealizado()->emReais());
    }

    #[Test]
    public function fixoVeDiasDoCicloSemValoresMenosAusenciasESemDuplicarIntegralRegistrado(): void
    {
        // ciclo 24x72 a partir de 01/11: serviço em 01, 05, 09, 13, 17, 21, 25 e 29/11
        $fixo = Objetos::fixo(9, '2025-11-01', 'Fixo Nove');
        $plantoes = $this->plantoesEmMemoria([
            Objetos::plantao(Turno::Integral, TipoBombeiro::Fixo, bombeiroId: 9, data: '2025-11-09'), // já registrado
            Objetos::plantao(Turno::Diurno, TipoBombeiro::Fixo, bombeiroId: 9, data: '2025-11-10'),   // extra fora do ciclo
        ]);

        $portal = $this->portal($plantoes, bombeiroId: 9, bombeiro: $fixo, ausencias: ['2025-11-05'])
            ->executar(1, MesEscala::de(2025, 11), DataEscala::deTexto('2025-11-12'));

        $datas = array_map(static fn ($p): string => $p->data->emTexto(), $portal->plantoesDoMes);
        self::assertSame(['2025-11-01', '2025-11-09', '2025-11-10', '2025-11-13', '2025-11-17', '2025-11-21', '2025-11-25', '2025-11-29'], $datas, 'sem 05/11 (ausência) e sem 09/11 duplicado');
        self::assertTrue($portal->plantoesDoMes[0]->doCiclo);
        self::assertFalse($portal->plantoesDoMes[1]->doCiclo, '09/11 é o plantão registrado');
        self::assertFalse($portal->mostraValores(), 'salário do Fixo varia: só a escala aparece');
        self::assertNull($portal->plantoesDoMes[0]->valor);
        self::assertNull($portal->plantoesDoMes[2]->valor, 'nem o plantão extra registrado tem valor');
        self::assertSame(7 * 24 + 12, $portal->horasDoMes());
        self::assertTrue($portal->ehFixo);
    }

    #[Test]
    public function loginSemBombeiroVinculadoNaoConsultaPlantoes(): void
    {
        $plantoes = $this->createMock(PlantaoRepository::class);
        $plantoes->expects(self::never())->method('doBombeiro');

        $portal = $this->portal($plantoes, bombeiroId: null)->executar(1, MesEscala::de(2025, 11), DataEscala::deTexto('2025-11-10'));

        self::assertFalse($portal->vinculado);
        self::assertSame([], $portal->plantoesDoMes);
    }

    /** @param list<string> $ausencias datas de exceção do Fixo */
    private function portal(PlantaoRepository $plantoes, ?int $bombeiroId, ?Bombeiro $bombeiro = null, array $ausencias = []): ConsultarMeuPortal
    {
        $usuarios = $this->createStub(UsuarioRepository::class);
        $usuarios->method('buscarPorId')->willReturn(new Usuario(1, 'Ana', 'ana', 'h', PapelUsuario::Bombeiro, true, bombeiroId: $bombeiroId));
        $bombeiros = $this->createStub(BombeiroRepository::class);
        $bombeiros->method('buscar')->willReturn($bombeiroId === null ? null : ($bombeiro ?? Objetos::bc($bombeiroId)));
        $excecoes = $this->createStub(ExcecaoFixoRepository::class);
        $excecoes->method('doPeriodo')->willReturn(new ExcecoesDoPeriodo(array_map(
            static fn (string $data): array => ['bombeiroId' => (int) $bombeiro?->id, 'data' => DataEscala::deTexto($data)],
            $ausencias,
        )));

        return new ConsultarMeuPortal($usuarios, $bombeiros, $plantoes, $excecoes, new CicloFixo(), new TabelaDeRemuneracao(Dinheiro::reais(125), Dinheiro::reais(250)));
    }

    /** @param list<Plantao> $todos doPeriodo falha: o portal nunca deve carregar a escala de todos */
    private function plantoesEmMemoria(array $todos): PlantaoRepository
    {
        return new class ($todos) implements PlantaoRepository {
            /** @param list<Plantao> $todos */
            public function __construct(private array $todos)
            {
            }

            public function doBombeiro(int $bombeiroId, DataEscala $inicio, DataEscala $fim): array
            {
                return array_values(array_filter($this->todos, static fn (Plantao $p): bool => $p->bombeiroId === $bombeiroId
                    && $p->data->emTexto() >= $inicio->emTexto() && $p->data->emTexto() <= $fim->emTexto()));
            }

            public function doPeriodo(DataEscala $inicio, DataEscala $fim): array
            {
                throw new \LogicException('o portal não deve carregar plantões de outros bombeiros');
            }

            public function doDia(DataEscala $data, bool $bloquearParaEscrita = false): array
            {
                throw new \LogicException('não usado');
            }

            public function inserir(int $bombeiroId, DataEscala $data, Turno $turno): int
            {
                throw new \LogicException('não usado');
            }

            public function remover(int $plantaoId): bool
            {
                throw new \LogicException('não usado');
            }
        };
    }

    #[Test]
    public function planilhaMarcaCicloDoFixoRespeitaExcecaoEConverteSubstituto(): void
    {
        $fixo = Objetos::fixo(1, '2025-11-01', 'Fixo Um');
        $bc = Objetos::bc(2, 'Bc Dois');
        $bombeiros = $this->createStub(BombeiroRepository::class);
        $bombeiros->method('ativos')->willReturn([$bc, $fixo]);
        $plantoes = $this->createStub(PlantaoRepository::class);
        $plantoes->method('doPeriodo')->willReturn([
            Objetos::plantao(Turno::IntegralSubstituto, TipoBombeiro::Bc, bombeiroId: 2, data: '2025-11-05'),
            Objetos::plantao(Turno::Diurno, TipoBombeiro::Bc, bombeiroId: 2, data: '2025-11-06'),
            Objetos::plantao(Turno::Noturno, TipoBombeiro::Bc, bombeiroId: 2, data: '2025-11-06', id: 2),
        ]);
        $excecoes = $this->createStub(ExcecaoFixoRepository::class);
        $excecoes->method('doPeriodo')->willReturn(new ExcecoesDoPeriodo([['bombeiroId' => 1, 'data' => DataEscala::deTexto('2025-11-05')]]));

        $planilha = (new GerarPlanilhaMensal($bombeiros, $plantoes, $excecoes, new CicloFixo()))->executar(MesEscala::de(2025, 11));
        [$linhaBc, $linhaFixo] = $planilha->linhas;

        self::assertSame(['I'], $linhaFixo->codigos(1));
        self::assertSame([], $linhaFixo->codigos(5), 'exceção registrada: o Fixo não aparece no dia');
        self::assertSame(['I'], $linhaFixo->codigos(9));
        self::assertSame(['I'], $linhaBc->codigos(5), 'I_SUB aparece como I na planilha');
        self::assertSame(['D', 'N'], $linhaBc->codigos(6), 'dois plantões no mesmo dia não se sobrescrevem');
    }

    /** @return iterable<string, array{string, ?string, string}> */
    public static function nomes(): iterable
    {
        yield 'nome de guerra no fim' => ['Maria Souza', 'souza', 'MARIA <b>SOUZA</b>'];
        yield 'nome de guerra no início' => ['Rita Lopes Prado', 'RITA', '<b>RITA</b> LOPES PRADO'];
        yield 'palavra inteira, não prefixo' => ['Fabiane Rocha', 'FABIAN', 'FABIANE ROCHA (<b>FABIAN</b>)'];
        yield 'sem nome de guerra destaca o último' => ['José Carlos Lima', null, 'JOSÉ CARLOS <b>LIMA</b>'];
        yield 'HTML é escapado' => ['a<b> x', '', 'A&lt;B&gt; <b>X</b>'];
    }

    #[Test]
    #[DataProvider('nomes')]
    public function formataNomeComDestaque(string $nome, ?string $guerra, string $esperado): void
    {
        self::assertSame($esperado, (new FormatadorDeNomeDestaque())->formatar($nome, $guerra));
    }
}
