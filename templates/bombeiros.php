<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var list<\Escala\Domain\Bombeiro\Bombeiro> $bombeiros
 * @var array{tipo: string, mensagem: string}|null $flash
 */
$titulo = 'Bombeiros';
$ativo = 'bombeiros';
$totalAtivos = count(array_filter($bombeiros, static fn ($b): bool => $b->ativo));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require __DIR__ . '/_cabeca.php'; ?>
</head>
<body>
<?php require __DIR__ . '/_topo.php'; ?>

<main class="pagina">
    <div class="cabecalho">
        <div>
            <h1>Bombeiros</h1>
            <p><?= $totalAtivos ?> ativos de <?= count($bombeiros) ?> cadastrados</p>
        </div>
    </div>

    <?php if ($flash !== null): ?>
        <div class="aviso aviso--<?= $flash['tipo'] === 'success' ? 'sucesso' : 'erro' ?>" role="status"><?= $v->e($flash['mensagem']) ?></div>
    <?php endif; ?>

    <section class="cartao">
        <div class="cartao__titulo"><h2>Novo bombeiro</h2></div>
        <form class="cartao__corpo form-linha" action="bombeiros.php" method="post">
            <?= $v->csrfCampo() ?>
            <div class="campo">
                <label for="nome_completo">Nome completo</label>
                <input type="text" id="nome_completo" name="nome_completo" required>
            </div>
            <div class="campo">
                <label for="nome_guerra">Nome de guerra <span class="mudo">(opcional)</span></label>
                <input type="text" id="nome_guerra" name="nome_guerra" maxlength="60">
            </div>
            <div class="campo">
                <label for="add_tipo">Tipo</label>
                <select id="add_tipo" name="tipo" required>
                    <option value="">Selecione…</option>
                    <option value="BC">BC (voluntário)</option>
                    <option value="Fixo">Fixo (efetivo)</option>
                </select>
            </div>
            <button type="submit" class="botao botao--primario" name="add_bombeiro"><?= $v->icone('mais') ?> Adicionar</button>
        </form>
    </section>

    <section class="cartao">
        <?php if ($bombeiros === []): ?>
            <div class="cartao__corpo mudo">Nenhum bombeiro cadastrado.</div>
        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="num">ID</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Referência do ciclo</th>
                        <th>Situação</th>
                        <th class="acoes"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bombeiros as $b): ?>
                        <tr class="<?= $b->ativo ? '' : 'linha-inativa' ?>">
                            <td class="num"><?= (int) $b->id ?></td>
                            <td>
                                <?= $v->e($b->nomeCompleto) ?>
                                <?php if ($b->nomeGuerra !== null): ?><span class="mudo"> · <?= $v->e($b->nomeGuerra) ?></span><?php endif; ?>
                            </td>
                            <td><?= $b->ehFixo() ? $v->etiquetaFixo() : '<span class="turno turno--fixo">BC</span>' ?></td>
                            <td class="mudo">
                                <?php if ($b->ehFixo() && $b->referenciaCiclo !== null): ?>
                                    <?= $v->e($v->dataBr($b->referenciaCiclo->data->emTexto())) ?> · dia <?= $b->referenciaCiclo->diaDoCiclo ?>
                                <?php elseif ($b->ehFixo()): ?>
                                    <span class="texto-perigo">não definida</span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= $b->ativo ? '<span class="selo selo--ok">Ativo</span>' : '<span class="selo">Inativo</span>' ?></td>
                            <td class="acoes">
                                <a class="botao botao--pequeno" href="editar_bombeiro.php?id=<?= (int) $b->id ?>"><?= $v->icone('editar') ?> Editar</a>
                                <?php if ($b->ativo): ?>
                                    <form action="bombeiros.php" method="post" data-confirmar="Inativar <?= $v->e($b->nomeCompleto) ?>? Ele deixa de aparecer na escala e na ordem de escolha.">
                                        <?= $v->csrfCampo() ?>
                                        <input type="hidden" name="delete_bombeiro_id" value="<?= (int) $b->id ?>">
                                        <button type="submit" class="botao botao--pequeno botao--perigo"><?= $v->icone('inativar') ?> Inativar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/_confirmacao.php'; ?>
<script src="<?= $v->recurso('js/script.js') ?>"></script>
</body>
</html>
