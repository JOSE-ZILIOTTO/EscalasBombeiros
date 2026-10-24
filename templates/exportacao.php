<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var \Escala\Application\Exportacao\PlanilhaMensal $planilha
 * @var \Escala\Application\Exportacao\FormatadorDeNomeDestaque $formatador
 * @var \Escala\Domain\Planilha\AssinaturaDaPlanilha $assinatura
 * @var array{sucesso: bool, mensagem: string}|null $aviso
 * @var string $hoje data atual (Y-m-d), destacada na tela
 */
$mes = $planilha->mes;
$anterior = $mes->anterior();
$proximo = $mes->proximo();
$titulo = 'Planilha ' . $mes->nome() . ' ' . $mes->ano;
$ativo = 'escala';
$diasDaSemana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];
$classesDoCodigo = ['D' => 'turno--d', 'N' => 'turno--n', 'I' => 'turno--i'];
$totalDePlantoes = array_sum(array_map(
    static fn($linha): int => array_sum(array_map('count', $linha->codigosPorDia)),
    $planilha->linhas,
));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require __DIR__ . '/_cabeca.php'; ?>
</head>

<body>
    <?php require __DIR__ . '/_topo.php'; ?>

    <main class="pagina pagina--larga">
        <div class="cabecalho so-tela">
            <div>
                <a class="cabecalho__voltar" href="escala.php?month=<?= $mes->mes ?>&year=<?= $mes->ano ?>"><?= $v->icone('voltar') ?> Voltar à escala</a>
                <h1>Planilha de <?= $v->e($mes->nome() . ' de ' . $mes->ano) ?></h1>
                <p><?= count($planilha->linhas) ?> bombeiros · <?= $totalDePlantoes ?> plantões no mês · impressão em paisagem</p>
            </div>
            <div class="navegacao-mes">
                <a class="botao botao--icone" href="?month=<?= $anterior->mes ?>&year=<?= $anterior->ano ?>" aria-label="Mês anterior"><?= $v->icone('anterior') ?></a>
                <a class="botao botao--icone" href="?month=<?= $proximo->mes ?>&year=<?= $proximo->ano ?>" aria-label="Próximo mês"><?= $v->icone('proximo') ?></a>
                <a class="botao" href="exportar_excel.php?month=<?= $mes->mes ?>&year=<?= $mes->ano ?>"><?= $v->icone('planilha') ?> Baixar Excel</a>
            <button type="button" class="botao botao--primario" onclick="window.print()"><?= $v->icone('impressora') ?> Imprimir ou salvar PDF</button>
            </div>
        </div>

        <?php if ($aviso !== null): ?>
            <div class="aviso aviso--<?= $aviso['sucesso'] ? 'sucesso' : 'erro' ?> so-tela" role="status"><?= $v->e($aviso['mensagem']) ?></div>
        <?php endif; ?>

        <section class="cartao planilha">
            <header class="planilha__cabecalho">
                <img src="<?= $v->recurso('img/logo.png') ?>" alt="">
                <div>
                    <h2>Planilha de plantões BC</h2>
                    <p><?= $v->e($mes->nome() . ' de ' . $mes->ano) ?></p>
                </div>
                <div class="legenda">
                    <span><span class="turno turno--i">I</span> Integral 24h</span>
                    <span><span class="turno turno--d">D</span> Diurno 12h</span>
                    <span><span class="turno turno--n">N</span> Noturno 12h</span>
                </div>
            </header>

            <div class="planilha__rolagem">
                <table class="planilha__tabela">
                    <thead>
                        <tr>
                            <th class="planilha__nome" scope="col">Bombeiro</th>
                            <?php foreach ($mes->dias() as $dia): ?>
                                <th scope="col" class="<?= $dia->ehFimDeSemana() ? 'fim-de-semana' : '' ?> <?= $dia->emTexto() === $hoje ? 'hoje' : '' ?>">
                                    <span class="planilha__semana"><?= $diasDaSemana[$dia->diaDaSemana()] ?></span>
                                    <span class="planilha__dia"><?= $dia->dia() ?></span>
                                </th>
                            <?php endforeach; ?>
                            <th class="planilha__total" scope="col">Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planilha->linhas as $indice => $linha): ?>
                            <tr>
                                <th class="planilha__nome" scope="row">
                                    <span class="planilha__indice"><?= $indice + 1 ?></span>
                                    <?php // formatar() já devolve HTML escapado 
                                    ?>
                                    <span class="planilha__pessoa"><?= $formatador->formatar($linha->bombeiro->nomeCompleto, $linha->bombeiro->nomeGuerra) ?></span>
                                    <?php if ($linha->bombeiro->ehFixo()): ?><?= $v->etiquetaFixo() ?><?php endif; ?>
                                </th>
                                <?php foreach ($mes->dias() as $dia): ?>
                                    <td class="<?= $dia->ehFimDeSemana() ? 'fim-de-semana' : '' ?> <?= $dia->emTexto() === $hoje ? 'hoje' : '' ?>">
                                        <?php foreach ($linha->codigos($dia->dia()) as $codigo): ?>
                                            <span class="turno <?= $classesDoCodigo[$codigo] ?? '' ?>"><?= $v->e($codigo) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="planilha__total"><?= $linha->horas() > 0 ? $linha->horas() . 'h' : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <footer class="planilha__assinatura">
                <div class="planilha__linha-assinatura"></div>
                <strong><?= $v->e($assinatura->nome) ?></strong>
                <span><?= $v->e($assinatura->cargo) ?></span>
                <?php if ($assinatura->unidade !== ''): ?><span><?= $v->e($assinatura->unidade) ?></span><?php endif; ?>

                <button type="button" class="botao botao--pequeno botao--fantasma planilha__editar so-tela" data-abrir-assinatura>
                    <?= $v->icone('editar') ?> Editar assinatura
                </button>
            </footer>
        </section>


        <dialog class="dialogo modal__caixa" id="modal-assinatura" aria-labelledby="modal-assinatura-titulo">
            <form method="post">
                <?= $v->csrfCampo() ?>
                <input type="hidden" name="month" value="<?= $mes->mes ?>">
                <input type="hidden" name="year" value="<?= $mes->ano ?>">
                <div class="modal__cabecalho">
                    <h2 id="modal-assinatura-titulo">Assinatura da planilha</h2>
                    <button type="button" class="botao botao--fantasma botao--icone" data-fechar-assinatura aria-label="Fechar"><?= $v->icone('fechar') ?></button>
                </div>
                <div class="modal__corpo dialogo__corpo">
                    <div class="campo">
                        <label for="assinatura_nome">Responsável</label>
                        <input type="text" id="assinatura_nome" name="assinatura_nome" value="<?= $v->e($assinatura->nome) ?>" maxlength="120" required autofocus>
                    </div>
                    <div class="campo">
                        <label for="assinatura_cargo">Cargo</label>
                        <input type="text" id="assinatura_cargo" name="assinatura_cargo" value="<?= $v->e($assinatura->cargo) ?>" maxlength="120" required>
                    </div>
                    <div class="campo">
                        <label for="assinatura_unidade">Unidade <span class="mudo">(opcional)</span></label>
                        <input type="text" id="assinatura_unidade" name="assinatura_unidade" value="<?= $v->e($assinatura->unidade) ?>" maxlength="120">
                        <small>Vale para a planilha de todos os meses.</small>
                    </div>
                </div>
                <div class="rodape-form">
                    <button type="button" class="botao" data-fechar-assinatura>Cancelar</button>
                    <button type="submit" class="botao botao--primario">Salvar assinatura</button>
                </div>
            </form>
        </dialog>

        <script>
            (function() {
                const dialogo = document.getElementById('modal-assinatura');
                const form = dialogo.querySelector('form');
                document.querySelector('[data-abrir-assinatura]').addEventListener('click', () => {
                    form.reset(); // descarta o que foi digitado e cancelado antes
                    dialogo.showModal();
                });
                dialogo.querySelectorAll('[data-fechar-assinatura]').forEach((botao) => botao.addEventListener('click', () => dialogo.close()));
                // clique no fundo escurecido fecha (o alvo é o próprio <dialog>, fora da caixa)
                dialogo.addEventListener('click', (evento) => {
                    if (evento.target === dialogo) dialogo.close();
                });
            })();
        </script>
</body>

</html>