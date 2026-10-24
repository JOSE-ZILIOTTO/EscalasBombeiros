<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Domain;

use Escala\Domain\Excecao\BombeiroNaoEBcAtivo;
use Escala\Domain\OrdemEscolha\EstadoOrdemEscolha;
use Escala\Domain\OrdemEscolha\OrdemDeEscolha;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrdemDeEscolhaTest extends TestCase
{
    private const BCS = [1, 2, 3, 6];

    #[Test]
    public function sequenciaComecaLogoAposQuemIniciou(): void
    {
        $ordem = new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(ultimoQueIniciou: 2));

        self::assertSame([3, 6, 1, 2], $ordem->sequencia());
    }

    #[Test]
    public function quemIniciouInativoNaoRotacionaASequencia(): void
    {
        $ordem = new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(ultimoQueIniciou: 99));

        self::assertSame(self::BCS, $ordem->sequencia());
    }

    #[Test]
    public function sugeridoEOdaVezOuOPrimeiroDaSequencia(): void
    {
        self::assertSame(6, (new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(2, daVez: 6)))->sugerido());
        self::assertSame(3, (new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(2, daVez: null)))->sugerido());
        self::assertSame(3, (new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(2, daVez: 99)))->sugerido(), 'da vez inativo é ignorado');
    }

    #[Test]
    public function avancarPassaParaOProximoDaSequenciaCircular(): void
    {
        $ordem = new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(ultimoQueIniciou: 2, daVez: 2));

        self::assertSame(3, $ordem->avancada()->estado->daVez, 'o último da sequência volta ao início');
    }

    /**
     * Compatibilidade: o sistema antigo inicializava a ordem ao abrir o painel e só então
     * avançava; o primeiro clique em "avançar" tem que levar ao segundo da sequência.
     */
    #[Test]
    public function avancarSemNinguemNaVezIniciaEPassaParaOSegundo(): void
    {
        $ordem = new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(ultimoQueIniciou: 2));

        $avancada = $ordem->avancada();

        self::assertSame(6, $avancada->estado->daVez);
        self::assertSame(3, $avancada->estado->ultimoQueIniciou);
    }

    #[Test]
    public function leituraNaoAlteraOEstado(): void
    {
        $estado = new EstadoOrdemEscolha(2, null, null);
        $ordem = new OrdemDeEscolha(self::BCS, $estado);

        $ordem->sugerido();
        $ordem->sequencia();

        self::assertSame($estado, $ordem->estado);
    }

    #[Test]
    public function definirInicioColocaNaVezOSeguinteAoEscolhido(): void
    {
        $nova = (new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(2)))->comInicio(6);

        self::assertSame(1, $nova->estado->daVez);
        self::assertSame(6, $nova->estado->inicioSelecionado);
    }

    #[Test]
    public function definirInicioComQuemNaoEBcAtivoERecusado(): void
    {
        $this->expectException(BombeiroNaoEBcAtivo::class);

        (new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha()))->comInicio(99);
    }

    #[Test]
    public function inativarQuemEstaNaVezLiberaAVez(): void
    {
        $ordem = new OrdemDeEscolha(self::BCS, new EstadoOrdemEscolha(daVez: 3));

        self::assertNull($ordem->semBombeiroNaVez(3)->estado->daVez);
        self::assertSame(3, $ordem->semBombeiroNaVez(1)->estado->daVez);
    }

    #[Test]
    public function semBcsAtivosNaoHaSugerido(): void
    {
        $ordem = new OrdemDeEscolha([], new EstadoOrdemEscolha(1, 1));

        self::assertNull($ordem->sugerido());
        self::assertNull($ordem->avancada()->estado->daVez);
    }
}
