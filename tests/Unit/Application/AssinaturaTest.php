<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Application;

use Escala\Application\Exportacao\SalvarAssinatura;
use Escala\Domain\Excecao\AssinaturaInvalida;
use Escala\Domain\Planilha\AssinaturaDaPlanilha;
use Escala\Domain\Planilha\AssinaturaRepository;
use Escala\Tests\Support\TransacaoImediata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AssinaturaTest extends TestCase
{
    #[Test]
    public function normalizaEspacos(): void
    {
        $assinatura = new AssinaturaDaPlanilha('  2º SGT   Fulano ', "Coordenador\tBC", '');

        self::assertSame('2º SGT Fulano', $assinatura->nome);
        self::assertSame('Coordenador BC', $assinatura->cargo);
        self::assertSame('', $assinatura->unidade);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function invalidas(): iterable
    {
        yield 'sem nome' => ['  ', 'Coordenador BC', 'Unidade'];
        yield 'sem cargo' => ['Fulano', '', 'Unidade'];
        yield 'nome longo demais' => [str_repeat('á', 121), 'Coordenador BC', ''];
        yield 'texto fora de UTF-8' => ["2� SGT", 'Coordenador BC', ''];
        yield 'unidade longa demais' => ['Fulano', 'Coordenador BC', str_repeat('x', 121)];
    }

    #[Test]
    #[DataProvider('invalidas')]
    public function rejeitaDadosInvalidos(string $nome, string $cargo, string $unidade): void
    {
        $this->expectException(AssinaturaInvalida::class);
        new AssinaturaDaPlanilha($nome, $cargo, $unidade);
    }

    #[Test]
    public function salvarGravaAssinaturaValidada(): void
    {
        $repositorio = $this->createMock(AssinaturaRepository::class);
        $repositorio->expects(self::once())->method('salvar')
            ->with(self::callback(static fn (AssinaturaDaPlanilha $a): bool => $a->nome === 'Fulano' && $a->unidade === 'Unidade X'));

        (new SalvarAssinatura(new TransacaoImediata(), $repositorio))->executar(' Fulano ', 'Coordenador BC', 'Unidade X');
    }

    #[Test]
    public function salvarNaoGravaQuandoInvalida(): void
    {
        $repositorio = $this->createMock(AssinaturaRepository::class);
        $repositorio->expects(self::never())->method('salvar');

        $this->expectException(AssinaturaInvalida::class);
        (new SalvarAssinatura(new TransacaoImediata(), $repositorio))->executar('', 'Coordenador BC', '');
    }
}
