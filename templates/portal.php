<?php

declare(strict_types=1);

use Escala\Domain\Escala\Turno;

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var \Escala\Domain\Usuario\Usuario $usuario
 * @var \Escala\Application\Portal\MeuPortal $portal
 * @var \Escala\Domain\Escala\DataEscala $hoje
 * @var \Escala\Domain\Escala\MesEscala $mesAtual
 */
$titulo = 'Minha escala';
$ativo = 'portal';
$mes = $portal->mes;
$anterior = $mes->anterior();
$proximo = $mes->proximo();
$ehMesAtual = $mes->ano === $mesAtual->ano && $mes->mes === $mesAtual->mes;
$ehMesFuturo = $mes->ano * 12 + $mes->mes > $mesAtual->ano * 12 + $mesAtual->mes;
$diasDaSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$nomesDosTurnos = [
    Turno::Diurno->value => 'Diurno',
    Turno::Noturno->value => 'Noturno',
    Turno::Integral->value => 'Integral',
    Turno::IntegralSubstituto->value => 'Integral (substituto)',
];
$quantidade = count($portal->plantoesDoMes);
$realizados = $portal->realizados();
$comValores = $portal->mostraValores();
$periodoRealizado = $ehMesAtual ? ' até hoje' : ($ehMesFuturo ? ' · o mês ainda não começou' : ' no mês');
$aRealizar = $portal->aRealizar();
$nomeDoTurno = static fn (\Escala\Application\Portal\MeuPlantao $p): string => $p->doCiclo ? 'Integral (ciclo do Fixo)' : $nomesDosTurnos[$p->turno->value];
$plural = static fn (int $n, string $um, string $varios): string => $n . ' ' . ($n === 1 ? $um : $varios);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require __DIR__ . '/_cabeca.php'; ?>
</head>
<body>
<?php require __DIR__ . '/_topo.php'; ?>

<main class="pagina pagina--estreita">
    <div class="cabecalho">
        <div>
            <h1>Minha escala</h1>
            <p>Olá, <?= $v->e($usuario->nome) ?>. <?= $portal->mostraValores() ? 'Aqui estão os seus plantões e valores.' : 'Aqui está a sua escala de serviço.' ?></p>
        </div>
        <nav class="navegacao-mes" aria-label="Mês">
            <a class="botao botao--icone" href="?month=<?= $anterior->mes ?>&year=<?= $anterior->ano ?>" aria-label="Mês anterior"><?= $v->icone('anterior') ?></a>
            <strong class="navegacao-mes__rotulo"><?= $v->e($mes->nome() . ' de ' . $mes->ano) ?></strong>
            <a class="botao botao--icone" href="?month=<?= $proximo->mes ?>&year=<?= $proximo->ano ?>" aria-label="Próximo mês"><?= $v->icone('proximo') ?></a>
            <?php if (!$ehMesAtual): ?>
                <a class="botao" href="portal_usuario.php">Mês atual</a>
            <?php endif; ?>
        </nav>
    </div>

    <?php if (!$portal->vinculado): ?>
        <div class="aviso aviso--erro" role="status">
            Seu login ainda não está ligado a um bombeiro, por isso não há plantões para mostrar.
            Peça a um administrador para fazer o vínculo na tela Usuários.
        </div>
    <?php else: ?>
        <section class="resumo" aria-label="Resumo de <?= $v->e($mes->nome()) ?>">
            <?php if ($comValores): ?>
                <div class="cartao indicador indicador--destaque">
                    <div class="indicador__rotulo">Total de <?= $v->e($mes->nome()) ?></div>
                    <div class="indicador__valor"><?= $v->dinheiro($portal->totalDoMes()) ?></div>
                    <div class="indicador__detalhe"><?= $plural($quantidade, 'plantão', 'plantões') ?> · <?= $portal->horasDoMes() ?>h</div>
                </div>
                <div class="cartao indicador">
                    <div class="indicador__rotulo">Já realizado</div>
                    <div class="indicador__valor"><?= $v->dinheiro($portal->valorRealizado()) ?></div>
                    <div class="indicador__detalhe"><?= $plural(count($realizados), 'plantão', 'plantões') ?><?= $periodoRealizado ?></div>
                </div>
                <div class="cartao indicador">
                    <div class="indicador__rotulo">A realizar</div>
                    <div class="indicador__valor"><?= $v->dinheiro($portal->valorARealizar()) ?></div>
                    <div class="indicador__detalhe"><?= $plural(count($aRealizar), 'plantão agendado', 'plantões agendados') ?></div>
                </div>
            <?php else: ?>
                <?php // Fixo: só a escala, sem valores (o salário dele não segue a tabela por plantão) ?>
                <div class="cartao indicador indicador--destaque">
                    <div class="indicador__rotulo">Plantões em <?= $v->e($mes->nome()) ?></div>
                    <div class="indicador__valor"><?= $quantidade ?></div>
                    <div class="indicador__detalhe"><?= $portal->horasDoMes() ?>h de serviço</div>
                </div>
                <div class="cartao indicador">
                    <div class="indicador__rotulo">Já realizados</div>
                    <div class="indicador__valor"><?= count($realizados) ?></div>
                    <div class="indicador__detalhe"><?= ltrim($periodoRealizado, ' ·') ?: 'no mês' ?></div>
                </div>
                <div class="cartao indicador">
                    <div class="indicador__rotulo">A realizar</div>
                    <div class="indicador__valor"><?= count($aRealizar) ?></div>
                    <div class="indicador__detalhe"><?= count($aRealizar) === 1 ? 'agendado' : 'agendados' ?></div>
                </div>
            <?php endif; ?>
        </section>

        <section class="cartao">
            <div class="cartao__titulo">
                <h2>Plantões de <?= $v->e($mes->nome() . ' de ' . $mes->ano) ?></h2>
                <?php if ($quantidade > 0): ?>
                    <span class="mudo"><?= count($realizados) ?> de <?= $quantidade ?> realizados</span>
                <?php endif; ?>
            </div>

            <?php if ($quantidade === 0): ?>
                <p class="vazio-lista">Você não tem plantões em <?= $v->e($mes->nome()) ?>.</p>
            <?php else: ?>
                <table class="tabela tabela--plantoes">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Turno</th>
                            <th class="so-largo">Horas</th>
                            <?php if ($comValores): ?><th class="numero">Valor</th><?php endif; ?>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portal->plantoesDoMes as $p): ?>
                            <?php $ehHoje = $p->data->emTexto() === $hoje->emTexto(); ?>
                            <tr class="<?= $ehHoje ? 'linha-hoje' : '' ?>">
                                <td>
                                    <span class="data-curta"><?= $v->e(substr($v->dataBr($p->data->emTexto()), 0, 5)) ?></span>
                                    <span class="mudo"><?= $diasDaSemana[$p->data->diaDaSemana()] ?></span>
                                </td>
                                <td><?= $v->etiquetaTurno($p->turno) ?> <span class="so-largo"><?= $nomeDoTurno($p) ?></span></td>
                                <td class="so-largo"><?= $p->horas() ?>h</td>
                                <?php if ($comValores && $p->valor !== null): ?><td class="numero"><?= $v->dinheiro($p->valor) ?></td><?php endif; ?>
                                <td>
                                    <?php if ($ehHoje): ?>
                                        <span class="selo selo--hoje">Hoje</span>
                                    <?php elseif ($p->realizado): ?>
                                        <span class="selo selo--ok">Realizado</span>
                                    <?php else: ?>
                                        <span class="selo">Agendado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Total do mês</td>
                            <td class="so-largo"><?= $portal->horasDoMes() ?>h</td>
                            <?php if ($comValores): ?><td class="numero"><?= $v->dinheiro($portal->totalDoMes()) ?></td><?php endif; ?>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </section>

        <p class="nota-valores">
            <?php if ($portal->ehFixo): ?>
                Seus dias de serviço do ciclo 24x72 aparecem como plantão integral, exceto os dias marcados como ausência.
            <?php else: ?>
            Valor por plantão: 12 horas (diurno ou noturno) <?= $v->dinheiro($portal->tabela->turno12Horas) ?> ·
            24 horas (integral) <?= $v->dinheiro($portal->tabela->turno24Horas) ?>.
            <?php endif; ?>
        </p>
    <?php endif; ?>
</main>
</body>
</html>
