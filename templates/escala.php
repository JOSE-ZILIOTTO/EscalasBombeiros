<?php

declare(strict_types=1);

use Escala\Domain\Escala\Turno;

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var \Escala\Application\Escala\CalendarioDoMes $calendario
 * @var string $hoje Y-m-d
 * @var bool $abrirTutorial
 */
$mes = $calendario->mes;
$ordem = $calendario->ordem;
$anterior = $mes->anterior();
$proximo = $mes->proximo();
$titulo = $mes->nome() . ' ' . $mes->ano;
$comCsrf = true;
$ativo = 'escala';
$colunaInicial = $mes->primeiroDia()->diaDaSemana();
$celulasVazias = (7 - ($colunaInicial + $mes->quantidadeDeDias()) % 7) % 7;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require __DIR__ . '/_cabeca.php'; ?>
</head>
<body>
<?php require __DIR__ . '/_topo.php'; ?>

<main class="pagina">
    <section class="cartao barra-escala" aria-label="Mês e ordem de escolha">
        <div class="navegacao-mes" data-tutorial="mes">
            <a class="botao botao--icone" href="?month=<?= $anterior->mes ?>&year=<?= $anterior->ano ?>" aria-label="Mês anterior"><?= $v->icone('anterior') ?></a>
            <h1><?= $v->e($titulo) ?></h1>
            <a class="botao botao--icone" href="?month=<?= $proximo->mes ?>&year=<?= $proximo->ano ?>" aria-label="Próximo mês"><?= $v->icone('proximo') ?></a>
        </div>

        <div class="ordem" data-tutorial="ordem">
            <div class="ordem__item">
                <label class="rotulo" for="selectInicioOrdem">Início da ordem</label>
                <select id="selectInicioOrdem" <?= $ordem->sequencia === [] ? 'disabled' : '' ?>>
                    <?php if ($ordem->sequencia === []): ?>
                        <option value="">Nenhum BC ativo</option>
                    <?php endif; ?>
                    <?php foreach ($ordem->sequencia as $bc): ?>
                        <option value="<?= (int) $bc->id ?>" <?= $bc->id === $ordem->inicioSelecionado ? 'selected' : '' ?>><?= $v->e($bc->nomeCompleto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ordem__item">
                <span class="mudo">Próximo a escolher:</span>
                <strong class="ordem__sugerido" id="displayProximoSugerido"><?= $v->e($ordem->sugerido?->nomeCompleto ?? 'Nenhum') ?></strong>
                <button type="button" class="botao botao--pequeno" id="btnAvancarOrdem" <?= $ordem->sugerido === null ? 'disabled' : '' ?>>
                    Avançar <?= $v->icone('avancar') ?>
                </button>
            </div>
        </div>

        <div class="barra-escala__acoes" data-tutorial="planilha">
            <a class="botao" href="exportar_tabela_formatada.php?month=<?= $mes->mes ?>&year=<?= $mes->ano ?>" target="_blank" rel="noopener">
                <?= $v->icone('impressora') ?> Planilha
            </a>
            <a class="botao" href="exportar_excel.php?month=<?= $mes->mes ?>&year=<?= $mes->ano ?>" title="Baixar a planilha do mês em Excel (.xlsx)">
                <?= $v->icone('planilha') ?> Excel
            </a>
        </div>
    </section>

    <div class="cartao">
        <div class="cartao__titulo">
            <div class="legenda" aria-label="Legenda" data-tutorial="legenda">
                <span><?= $v->etiquetaFixo() ?> ciclo 24x72</span>
                <span><?= $v->etiquetaTurno(Turno::Diurno) ?> diurno</span>
                <span><?= $v->etiquetaTurno(Turno::Noturno) ?> noturno</span>
                <span><?= $v->etiquetaTurno(Turno::Integral) ?> integral</span>
                <span><?= $v->etiquetaTurno(Turno::IntegralSubstituto) ?> substituto do Fixo</span>
            </div>
            <span class="legenda"><span><span class="vaga vaga--livre">D 1</span> vagas de BC livres</span></span>
        </div>

        <table class="calendario">
            <thead>
                <tr><th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th></tr>
            </thead>
            <tbody>
                <tr>
                    <?= str_repeat('<td class="fora-do-mes"></td>', $colunaInicial) ?>
                    <?php foreach ($calendario->dias as $quadro): ?>
                        <?php
                        $data = $quadro->data->emTexto();
                        $vagas = $quadro->vagas;
                        if ($quadro->data->diaDaSemana() === 0 && $quadro->data->dia() !== 1) {
                            echo '</tr><tr>';
                        }
                        $classes = trim(($quadro->data->ehFimDeSemana() ? 'fim-de-semana ' : '') . ($data === $hoje ? 'hoje' : ''));
                        ?>
                        <td class="<?= $classes ?>">
                            <div class="dia__topo">
                                <span class="dia__numero"><?= $quadro->data->dia() ?></span>
                                <button type="button" class="dia__abrir btn-detalhes" data-date="<?= $data ?>" aria-label="Abrir dia <?= $v->dataBr($data) ?>" title="Detalhes e registro">
                                    <?= $v->icone('mais') ?>
                                </button>
                            </div>
                            <div class="vagas" data-vagas>
                                <span class="vaga <?= $vagas->diurno > 0 ? 'vaga--livre' : '' ?>" title="Vagas de BC no diurno">D <?= $vagas->diurno ?></span>
                                <span class="vaga <?= $vagas->noturno > 0 ? 'vaga--livre' : '' ?>" title="Vagas de BC no noturno">N <?= $vagas->noturno ?></span>
                                <?php if ($vagas->substituto > 0): ?>
                                    <span class="vaga vaga--livre" title="Vaga de integral substituto do Fixo">SUB</span>
                                <?php endif; ?>
                            </div>
                            <ul class="ocupantes" data-ocupantes>
                                <?php if ($quadro->temFixoValido()): ?>
                                    <li class="ocupante"><?= $v->etiquetaFixo() ?><span class="ocupante__nome" title="<?= $v->e($quadro->fixo->bombeiro->nomeCompleto) ?>"><?= $v->e($quadro->fixo->bombeiro->nomeCompleto) ?></span></li>
                                <?php endif; ?>
                                <?php foreach ($quadro->plantoes as $plantao): ?>
                                    <li class="ocupante"><?= $v->etiquetaTurno($plantao->turno) ?><span class="ocupante__nome" title="<?= $v->e($plantao->nomeBombeiro) ?>"><?= $v->e($plantao->nomeBombeiro) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    <?php endforeach; ?>
                    <?= str_repeat('<td class="fora-do-mes"></td>', $celulasVazias) ?>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<div id="detailsModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalDate">
    <div class="modal__caixa">
        <div class="modal__cabecalho">
            <h2 id="modalDate">Dia</h2>
            <button type="button" class="botao botao--fantasma botao--icone close-button" aria-label="Fechar">
                <svg class="icone" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal__corpo">
            <section>
                <h3>Escalados</h3>
                <ul class="lista-ocupantes" id="modalOcupantesList"></ul>
            </section>

            <section id="modalSelecao" hidden>
                <h3>Registrar plantão</h3>
                <div class="campo">
                    <label for="modalSelectBombeiro">Bombeiro</label>
                    <select id="modalSelectBombeiro"></select>
                    <small id="modalSugestao"></small>
                </div>
                <div class="turnos-botoes">
                    <button type="button" class="botao" id="modalBtnD" disabled><span>Diurno</span><span class="vagas-restantes"></span></button>
                    <button type="button" class="botao" id="modalBtnN" disabled><span>Noturno</span><span class="vagas-restantes"></span></button>
                    <button type="button" class="botao" id="modalBtnI" disabled><span>Integral 24h</span><span class="vagas-restantes"></span></button>
                    <button type="button" class="botao" id="modalBtnISUB" disabled><span>Substituto do Fixo</span><span class="vagas-restantes"></span></button>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_confirmacao.php'; ?>

<div id="toast-container" aria-live="polite"></div>

<script src="<?= $v->recurso('js/script.js') ?>"></script>
<script src="<?= $v->recurso('js/tutorial.js') ?>" <?= $abrirTutorial ? 'data-abrir-sozinho' : '' ?>></script>
</body>
</html>
