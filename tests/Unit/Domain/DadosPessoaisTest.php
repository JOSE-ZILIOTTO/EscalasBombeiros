<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Domain;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\Cpf;
use Escala\Domain\Bombeiro\DadosCadastrais;
use Escala\Domain\Bombeiro\PoliticaDeRetencao;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Excecao\AnonimizacaoNaoPermitida;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DadosPessoaisTest extends TestCase
{
    #[Test]
    public function cpfMascaradoMostraSoOsDigitosDoMeio(): void
    {
        self::assertSame('***.456.789-**', Cpf::criar('123.456.789-09')->mascarado());
        self::assertSame('***', Cpf::doBanco('123456')->mascarado()); // legado fora do padrão
    }

    #[Test]
    public function anonimizarApagaOsDadosEMantemONomeParaOHistorico(): void
    {
        $anonimizado = $this->inativo('2020-01-10')->anonimizado();

        self::assertTrue($anonimizado->anonimizado);
        self::assertFalse($anonimizado->temDadosPessoais());
        self::assertSame('Fulano de Tal', $anonimizado->nomeCompleto);
        self::assertSame('TAL', $anonimizado->nomeGuerra);
    }

    #[Test]
    public function bombeiroAtivoNaoPodeSerAnonimizado(): void
    {
        $ativo = new Bombeiro(1, 'Fulano', null, TipoBombeiro::Bc, true, null, $this->dados());

        $this->expectException(AnonimizacaoNaoPermitida::class);
        $ativo->anonimizado();
    }

    #[Test]
    public function prazoDeRetencaoVenceNoAniversarioDaInativacao(): void
    {
        $politica = new PoliticaDeRetencao(5);
        $bombeiro = $this->inativo('2020-03-15');

        self::assertFalse($politica->venceu($bombeiro, DataEscala::deTexto('2025-03-14')));
        self::assertTrue($politica->venceu($bombeiro, DataEscala::deTexto('2025-03-15')));
    }

    #[Test]
    public function semDadosOuJaAnonimizadoNaoHaoQueVencer(): void
    {
        $politica = new PoliticaDeRetencao(1);
        $hoje = DataEscala::deTexto('2030-01-01');

        self::assertFalse($politica->venceu($this->inativo('2020-01-01')->anonimizado(), $hoje));
        self::assertFalse($politica->venceu(new Bombeiro(1, 'Sem dados', null, TipoBombeiro::Bc, false, null, inativadoEm: DataEscala::deTexto('2020-01-01')), $hoje));
        self::assertFalse($politica->venceu(new Bombeiro(1, 'Ativo', null, TipoBombeiro::Bc, true, null, $this->dados()), $hoje));
    }

    private function inativo(string $inativadoEm): Bombeiro
    {
        return new Bombeiro(1, 'Fulano de Tal', 'TAL', TipoBombeiro::Bc, false, null, $this->dados(), DataEscala::deTexto($inativadoEm));
    }

    private function dados(): DadosCadastrais
    {
        return new DadosCadastrais(cpf: Cpf::criar('123.456.789-09'), telefonePrincipal: '(11) 90000-0000', dadosBancarios: 'Banco Exemplo, Ag 0001, CC 1234-5');
    }
}
