<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Infrastructure;

use Escala\Application\Exportacao\FormatadorDeNomeDestaque;
use Escala\Application\Exportacao\LinhaDaPlanilha;
use Escala\Application\Exportacao\PlanilhaMensal;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\MesEscala;
use Escala\Domain\Planilha\AssinaturaDaPlanilha;
use Escala\Infrastructure\Exportacao\ArquivoZip;
use Escala\Infrastructure\Exportacao\PlanilhaXlsx;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class PlanilhaXlsxTest extends TestCase
{
    #[Test]
    public function zipSemCompressaoAbreEmLeitorPadrao(): void
    {
        $zip = new ArquivoZip();
        $zip->adicionar('a.txt', 'olá');
        $zip->adicionar('pasta/b.xml', '<x/>');

        $leitor = $this->abrir($zip->conteudo());

        self::assertSame(2, $leitor->numFiles);
        self::assertSame('olá', $leitor->getFromName('a.txt'));
        self::assertSame('<x/>', $leitor->getFromName('pasta/b.xml'));
    }

    #[Test]
    public function linhaSomaHorasPorCodigo(): void
    {
        $linha = new LinhaDaPlanilha(self::bombeiro('ANA SOUZA', 'SOUZA'), [1 => ['I'], 2 => ['D', 'N'], 5 => ['D']]);

        self::assertSame(24 + 24 + 12, $linha->horas());
    }

    #[Test]
    public function folhaTemCabecalhoCodigosHorasNegritoEAssinatura(): void
    {
        $planilha = new PlanilhaMensal(MesEscala::de(2026, 10), [
            new LinhaDaPlanilha(self::bombeiro('MARIA SOUZA', 'SOUZA'), [3 => ['I']]),
            new LinhaDaPlanilha(self::bombeiro('JOÃO D\'ÁVILA & CIA <X>', null), [2 => ['D', 'N']]),
        ]);
        $assinatura = new AssinaturaDaPlanilha('3º SGT Fulano', 'Coordenador BC', '');

        $xlsx = new PlanilhaXlsx(new FormatadorDeNomeDestaque());
        $leitor = $this->abrir($xlsx->gerar($planilha, $assinatura));
        $folha = (string) $leitor->getFromName('xl/worksheets/sheet1.xml');

        foreach (['[Content_Types].xml', 'xl/workbook.xml', 'xl/styles.xml', '_rels/.rels'] as $parte) {
            self::assertNotFalse($leitor->getFromName($parte), $parte);
        }
        self::assertNotFalse(simplexml_load_string($folha), 'XML da folha bem formado');
        self::assertStringContainsString('PLANILHA PLANTÕES BC OUTUBRO 2026', $folha);
        self::assertStringContainsString('>SAB<', $folha, '03/10/2026 é sábado');
        self::assertMatchesRegularExpression('#<r><rPr><b/>[^<]*<sz[^>]*/><rFont[^>]*/></rPr><t xml:space="preserve">SOUZA</t></r>#', $folha);
        self::assertStringContainsString('<c r="D6" s="7" t="inlineStr"><is><t xml:space="preserve">I</t></is></c>', $folha);
        self::assertStringContainsString('<c r="AG6" s="11"><v>24</v></c>', $folha, 'horas como número');
        self::assertStringContainsString('>D/N<', $folha);
        // sem nome de guerra, a última palavra fica em negrito num trecho separado
        self::assertStringContainsString('2-JOÃO D&apos;ÁVILA &amp; CIA </t>', $folha, 'caracteres especiais escapados');
        self::assertStringContainsString('<b/><sz val="10"/><rFont val="Calibri"/></rPr><t xml:space="preserve">&lt;X&gt;</t>', $folha);
        self::assertStringContainsString('3º SGT Fulano', $folha);
        self::assertSame('Escala BC 2026-10.xlsx', $xlsx->nomeDoArquivo($planilha));
    }

    private function abrir(string $conteudo): ZipArchive
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('extensão zip indisponível para conferir o arquivo');
        }
        $arquivo = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($arquivo, $conteudo);
        register_shutdown_function(static fn () => @unlink($arquivo));

        $leitor = new ZipArchive();
        self::assertTrue($leitor->open($arquivo) === true, 'zip válido');
        return $leitor;
    }

    private static function bombeiro(string $nome, ?string $guerra): Bombeiro
    {
        return new Bombeiro(1, $nome, $guerra, TipoBombeiro::Bc, true, null);
    }
}
