<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Exportacao;

use Escala\Application\Exportacao\FormatadorDeNomeDestaque;
use Escala\Application\Exportacao\PlanilhaMensal;
use Escala\Domain\Planilha\AssinaturaDaPlanilha;

/**
 * Gera a planilha mensal em .xlsx (Office Open XML) no mesmo layout da versão impressa:
 * título, dias da semana com fim de semana destacado, um bombeiro por linha com o nome
 * de guerra em negrito, códigos I/D/N coloridos, total de horas e assinatura.
 */
final class PlanilhaXlsx
{
    private const DIAS_DA_SEMANA = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];
    private const PRIMEIRA_LINHA_DE_DADOS = 6;

    // índices em cellXfs (ver estilos())
    private const TITULO = 1;
    private const CABECALHO = 2;
    private const CABECALHO_FIM_DE_SEMANA = 3;
    private const NOME = 4;
    private const DIA = 5;
    private const DIA_FIM_DE_SEMANA = 6;
    private const CODIGO_I = 7;
    private const CODIGO_D = 8;
    private const CODIGO_N = 9;
    private const CODIGO_VARIOS = 10;
    private const HORAS = 11;
    private const ASSINATURA = 12;
    private const ASSINATURA_NOME = 13;
    private const CABECALHO_NOME = 14;
    private const SUBTITULO = 15;

    public function __construct(private readonly FormatadorDeNomeDestaque $formatador)
    {
    }

    public function gerar(PlanilhaMensal $planilha, AssinaturaDaPlanilha $assinatura): string
    {
        $zip = new ArquivoZip();
        $zip->adicionar('[Content_Types].xml', $this->tiposDeConteudo());
        $zip->adicionar('_rels/.rels', $this->relacoesDoPacote());
        $zip->adicionar('docProps/core.xml', $this->propriedades($planilha));
        $zip->adicionar('xl/workbook.xml', $this->pastaDeTrabalho($planilha));
        $zip->adicionar('xl/_rels/workbook.xml.rels', $this->relacoesDaPasta());
        $zip->adicionar('xl/styles.xml', $this->estilos());
        $zip->adicionar('xl/worksheets/sheet1.xml', $this->folha($planilha, $assinatura));
        return $zip->conteudo();
    }

    public function nomeDoArquivo(PlanilhaMensal $planilha): string
    {
        return sprintf('Escala BC %04d-%02d.xlsx', $planilha->mes->ano, $planilha->mes->mes);
    }

    private function folha(PlanilhaMensal $planilha, AssinaturaDaPlanilha $assinatura): string
    {
        $mes = $planilha->mes;
        $dias = $mes->dias();
        $colunaHoras = self::coluna(count($dias) + 2); // A = nome, B.. = dias, depois horas
        $linhas = [];

        $linhas[1] = $this->texto('A1', 'PLANILHA PLANTÕES BC ' . mb_strtoupper($mes->nome()) . ' ' . $mes->ano, self::TITULO);
        $linhas[2] = $this->texto('A2', $mes->nome() . ' de ' . $mes->ano, self::SUBTITULO);

        $semana = [$this->texto('A4', 'DIAS:', self::CABECALHO_NOME)];
        $numeros = [$this->texto('A5', 'NOME COMPLETO', self::CABECALHO_NOME)];
        foreach ($dias as $i => $dia) {
            $coluna = self::coluna($i + 2);
            $estilo = $dia->ehFimDeSemana() ? self::CABECALHO_FIM_DE_SEMANA : self::CABECALHO;
            $semana[] = $this->texto("{$coluna}4", self::DIAS_DA_SEMANA[$dia->diaDaSemana()], $estilo);
            $numeros[] = $this->numero("{$coluna}5", $dia->dia(), $estilo);
        }
        $semana[] = $this->texto("{$colunaHoras}4", 'HORAS', self::CABECALHO);
        $numeros[] = $this->texto("{$colunaHoras}5", '', self::CABECALHO);
        $linhas[4] = implode('', $semana);
        $linhas[5] = implode('', $numeros);

        $r = self::PRIMEIRA_LINHA_DE_DADOS;
        foreach ($planilha->linhas as $indice => $linha) {
            $nome = ($indice + 1) . '-' . $this->formatador->formatar($linha->bombeiro->nomeCompleto, $linha->bombeiro->nomeGuerra);
            $celulas = [$this->textoRico("A{$r}", $nome, self::NOME)];
            foreach ($dias as $i => $dia) {
                $codigos = $linha->codigos($dia->dia());
                $estilo = match (true) {
                    count($codigos) > 1 => self::CODIGO_VARIOS,
                    $codigos === ['I'] => self::CODIGO_I,
                    $codigos === ['D'] => self::CODIGO_D,
                    $codigos === ['N'] => self::CODIGO_N,
                    $dia->ehFimDeSemana() => self::DIA_FIM_DE_SEMANA,
                    default => self::DIA,
                };
                $celulas[] = $this->texto(self::coluna($i + 2) . $r, implode('/', $codigos), $estilo);
            }
            $celulas[] = $linha->horas() > 0
                ? $this->numero("{$colunaHoras}{$r}", $linha->horas(), self::HORAS)
                : $this->texto("{$colunaHoras}{$r}", '—', self::HORAS);
            $linhas[$r++] = implode('', $celulas);
        }
        $ultimaLinhaDeDados = $r - 1;

        // assinatura, duas linhas abaixo da tabela
        $r += 2;
        $mesclas = ["A1:{$colunaHoras}1", "A2:{$colunaHoras}2"];
        $blocoDaAssinatura = [
            [str_repeat('_', 40), self::ASSINATURA],
            [$assinatura->nome, self::ASSINATURA_NOME],
            [$assinatura->cargo, self::ASSINATURA],
        ];
        if ($assinatura->unidade !== '') {
            $blocoDaAssinatura[] = [$assinatura->unidade, self::ASSINATURA];
        }
        foreach ($blocoDaAssinatura as [$texto, $estilo]) {
            $linhas[$r] = $this->texto("A{$r}", $texto, $estilo);
            $mesclas[] = "A{$r}:{$colunaHoras}{$r}";
            $r++;
        }

        $xmlLinhas = '';
        foreach ($linhas as $numero => $conteudo) {
            $altura = match (true) {
                $numero === 1 => ' ht="24" customHeight="1"',
                $numero >= self::PRIMEIRA_LINHA_DE_DADOS && $numero <= $ultimaLinhaDeDados => ' ht="20" customHeight="1"',
                default => '',
            };
            $xmlLinhas .= "<row r=\"{$numero}\"{$altura}>{$conteudo}</row>";
        }
        $xmlMesclas = implode('', array_map(static fn (string $ref): string => "<mergeCell ref=\"{$ref}\"/>", $mesclas));
        $ultimaColunaDeDia = count($dias) + 1;
        $colunas = '<col min="1" max="1" width="38" customWidth="1"/>'
            . "<col min=\"2\" max=\"{$ultimaColunaDeDia}\" width=\"5.2\" customWidth=\"1\"/>"
            . '<col min="' . ($ultimaColunaDeDia + 1) . '" max="' . ($ultimaColunaDeDia + 1) . '" width="8" customWidth="1"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . "<dimension ref=\"A1:{$colunaHoras}" . ($r - 1) . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane xSplit="1" ySplit="5" topLeftCell="B6" activePane="bottomRight" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . "<cols>{$colunas}</cols>"
            . "<sheetData>{$xmlLinhas}</sheetData>"
            . '<mergeCells count="' . count($mesclas) . "\">{$xmlMesclas}</mergeCells>"
            . '<printOptions horizontalCentered="1"/>'
            . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    private function texto(string $ref, string $texto, int $estilo): string
    {
        if ($texto === '') {
            return "<c r=\"{$ref}\" s=\"{$estilo}\"/>";
        }
        return "<c r=\"{$ref}\" s=\"{$estilo}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">" . self::xml($texto) . '</t></is></c>';
    }

    private function numero(string $ref, int $valor, int $estilo): string
    {
        return "<c r=\"{$ref}\" s=\"{$estilo}\"><v>{$valor}</v></c>";
    }

    /** Converte o HTML do formatador ("MARIA <b>SOUZA</b>", já escapado) em texto com trechos em negrito. */
    private function textoRico(string $ref, string $html, int $estilo): string
    {
        $trechos = '';
        $negrito = false;
        foreach (preg_split('#(</?b>)#', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $parte) {
            if ($parte === '<b>' || $parte === '</b>') {
                $negrito = $parte === '<b>';
                continue;
            }
            $texto = self::xml(html_entity_decode($parte, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $fonte = '<rPr>' . ($negrito ? '<b/>' : '') . '<sz val="10"/><rFont val="Calibri"/></rPr>';
            $trechos .= "<r>{$fonte}<t xml:space=\"preserve\">{$texto}</t></r>";
        }
        return "<c r=\"{$ref}\" s=\"{$estilo}\" t=\"inlineStr\"><is>{$trechos}</is></c>";
    }

    private function estilos(): string
    {
        $fontes = [
            '<font><sz val="10"/><name val="Calibri"/></font>',                              // 0
            '<font><b/><sz val="10"/><name val="Calibri"/></font>',                          // 1
            '<font><b/><sz val="14"/><name val="Calibri"/></font>',                          // 2
            '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>',   // 3 sobre vermelho
            '<font><b/><sz val="10"/><color rgb="FF0B6B69"/><name val="Calibri"/></font>',   // 4 I
            '<font><b/><sz val="10"/><color rgb="FF93560A"/><name val="Calibri"/></font>',   // 5 D
            '<font><b/><sz val="10"/><color rgb="FF33449A"/><name val="Calibri"/></font>',   // 6 N
            '<font><sz val="10"/><color rgb="FF545D69"/><name val="Calibri"/></font>',       // 7 subtítulo
        ];
        $preenchimentos = [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
            self::preenchimento('FFF1F3F6'), // 2 cabeçalho
            self::preenchimento('FFB42318'), // 3 cabeçalho de fim de semana
            self::preenchimento('FFFDECEB'), // 4 coluna de fim de semana
            self::preenchimento('FFE0F2F1'), // 5 I
            self::preenchimento('FFFCF0DC'), // 6 D
            self::preenchimento('FFEAEDFB'), // 7 N
        ];
        $lado = '<color rgb="FFB9BFC8"/>';
        $bordas = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
            "<border><left style=\"thin\">{$lado}</left><right style=\"thin\">{$lado}</right>"
                . "<top style=\"thin\">{$lado}</top><bottom style=\"thin\">{$lado}</bottom><diagonal/></border>",
        ];
        $centro = '<alignment horizontal="center" vertical="center"/>';
        $esquerda = '<alignment horizontal="left" vertical="center" indent="1"/>';
        // [fonte, preenchimento, borda, formato numérico, alinhamento]
        $formatos = [
            [0, 0, 0, 0, ''],          // 0 padrão
            [2, 0, 0, 0, $centro],     // 1 título
            [1, 2, 1, 0, $centro],     // 2 cabeçalho
            [3, 3, 1, 0, $centro],     // 3 cabeçalho de fim de semana
            [0, 0, 1, 0, $esquerda],   // 4 nome
            [0, 0, 1, 0, $centro],     // 5 dia
            [0, 4, 1, 0, $centro],     // 6 dia de fim de semana
            [4, 5, 1, 0, $centro],     // 7 I
            [5, 6, 1, 0, $centro],     // 8 D
            [6, 7, 1, 0, $centro],     // 9 N
            [1, 0, 1, 0, $centro],     // 10 vários códigos (D/N)
            [1, 2, 1, 164, $centro],   // 11 horas
            [0, 0, 0, 0, $centro],     // 12 assinatura
            [1, 0, 0, 0, $centro],     // 13 assinatura (nome)
            [1, 2, 1, 0, $esquerda],   // 14 cabeçalho da coluna de nomes
            [7, 0, 0, 0, $centro],     // 15 subtítulo
        ];
        $xfs = '';
        foreach ($formatos as [$fonte, $preenchimento, $borda, $formato, $alinhamento]) {
            $xfs .= "<xf numFmtId=\"{$formato}\" fontId=\"{$fonte}\" fillId=\"{$preenchimento}\" borderId=\"{$borda}\" xfId=\"0\""
                . ($fonte > 0 ? ' applyFont="1"' : '')
                . ($preenchimento > 0 ? ' applyFill="1"' : '')
                . ($borda > 0 ? ' applyBorder="1"' : '')
                . ($formato > 0 ? ' applyNumberFormat="1"' : '')
                . ($alinhamento === '' ? '/>' : " applyAlignment=\"1\">{$alinhamento}</xf>");
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="0&quot;h&quot;"/></numFmts>'
            . '<fonts count="' . count($fontes) . '">' . implode('', $fontes) . '</fonts>'
            . '<fills count="' . count($preenchimentos) . '">' . implode('', $preenchimentos) . '</fills>'
            . '<borders count="' . count($bordas) . '">' . implode('', $bordas) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($formatos) . "\">{$xfs}</cellXfs>"
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private static function preenchimento(string $argb): string
    {
        return "<fill><patternFill patternType=\"solid\"><fgColor rgb=\"{$argb}\"/><bgColor indexed=\"64\"/></patternFill></fill>";
    }

    private function pastaDeTrabalho(PlanilhaMensal $planilha): string
    {
        $aba = self::xml($planilha->mes->nome() . ' ' . $planilha->mes->ano);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . "<sheets><sheet name=\"{$aba}\" sheetId=\"1\" r:id=\"rId1\"/></sheets>"
            // repete as duas linhas de cabeçalho em cada página impressa
            . "<definedNames><definedName name=\"_xlnm.Print_Titles\" localSheetId=\"0\">'{$aba}'!\$4:\$5</definedName></definedNames>"
            . '</workbook>';
    }

    private function relacoesDaPasta(): string
    {
        $tipo = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . "<Relationship Id=\"rId1\" Type=\"{$tipo}/worksheet\" Target=\"worksheets/sheet1.xml\"/>"
            . "<Relationship Id=\"rId2\" Type=\"{$tipo}/styles\" Target=\"styles.xml\"/>"
            . '</Relationships>';
    }

    private function relacoesDoPacote(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>';
    }

    private function tiposDeConteudo(): string
    {
        $planilha = 'application/vnd.openxmlformats-officedocument.spreadsheetml';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . "<Override PartName=\"/xl/workbook.xml\" ContentType=\"{$planilha}.sheet.main+xml\"/>"
            . "<Override PartName=\"/xl/worksheets/sheet1.xml\" ContentType=\"{$planilha}.worksheet+xml\"/>"
            . "<Override PartName=\"/xl/styles.xml\" ContentType=\"{$planilha}.styles+xml\"/>"
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>';
    }

    private function propriedades(PlanilhaMensal $planilha): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/">'
            . '<dc:title>' . self::xml('Planilha de plantões BC - ' . $planilha->mes->nome() . ' de ' . $planilha->mes->ano) . '</dc:title>'
            . '<dc:creator>Escala de Plantões</dc:creator>'
            . '</cp:coreProperties>';
    }

    /** 1 → A, 27 → AA */
    private static function coluna(int $numero): string
    {
        $letras = '';
        while ($numero > 0) {
            $resto = ($numero - 1) % 26;
            $letras = chr(65 + $resto) . $letras;
            $numero = intdiv($numero - 1, 26);
        }
        return $letras;
    }

    private static function xml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
