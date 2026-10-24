<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var array{sucesso: bool, mensagem: string}|null $aviso
 * @var array<string, mixed> $form valores do formulário (sem a senha)
 * @var bool $editando
 * @var bool $abrirFormulario abre o modal ao carregar (edição, ?novo ou erro de validação)
 * @var string|null $erroDoFormulario
 * @var list<\Escala\Domain\Usuario\Usuario> $usuarios
 * @var int $adminsAtivos
 * @var int|null $idLogado
 * @var array<int, string> $nomesDosBombeiros
 * @var list<\Escala\Domain\Bombeiro\Bombeiro> $bombeirosAtivos
 */
$titulo = 'Usuários';
$ativo = 'usuarios';
$valor = static fn (string $campo): string => is_string($form[$campo] ?? null) ? $form[$campo] : '';
$bombeiroSelecionado = (int) $valor('bombeiro_id');
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
            <h1>Usuários de acesso</h1>
            <p><?= count($usuarios) ?> login<?= count($usuarios) === 1 ? '' : 's' ?> · <?= $adminsAtivos ?> administrador<?= $adminsAtivos === 1 ? '' : 'es' ?> ativo<?= $adminsAtivos === 1 ? '' : 's' ?></p>
        </div>
        <a class="botao botao--primario" href="gera_hash.php?novo=1" data-novo-usuario><?= $v->icone('mais') ?> Novo usuário</a>
    </div>

    <?php if ($aviso !== null): ?>
        <div class="aviso aviso--<?= $aviso['sucesso'] ? 'sucesso' : 'erro' ?>" role="status"><?= $v->e($aviso['mensagem']) ?></div>
    <?php endif; ?>

    <section class="cartao">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Login</th>
                    <th>Perfil</th>
                    <th>Bombeiro vinculado</th>
                    <th>Situação</th>
                    <th class="acoes"><span class="sr-only">Ações</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <?php
                    $ehVoce = $u->id === $idLogado;
                    $ultimoAdmin = $u->ehAdminAtivo() && $adminsAtivos <= 1;
                    ?>
                    <tr class="<?= $u->ativo ? '' : 'linha-inativa' ?>">
                        <td><?= $v->e($u->nome) ?><?php if ($ehVoce): ?> <span class="selo selo--neutro">você</span><?php endif; ?></td>
                        <td class="mudo"><?= $v->e($u->login) ?></td>
                        <td><?= $u->ehAdmin() ? '<span class="turno turno--fixo">Administrador</span>' : '<span class="turno turno--n">Bombeiro</span>' ?></td>
                        <td class="mudo"><?= $u->bombeiroId === null ? '—' : $v->e($nomesDosBombeiros[$u->bombeiroId] ?? "#{$u->bombeiroId}") ?></td>
                        <td><?= $u->ativo ? '<span class="selo selo--ok">Ativo</span>' : '<span class="selo">Inativo</span>' ?></td>
                        <td class="acoes">
                            <a class="botao botao--pequeno" href="gera_hash.php?editar=<?= (int) $u->id ?>"><?= $v->icone('editar') ?> Editar</a>
                            <?php if ($u->ativo): ?>
                                <?php
                                $pergunta = $ehVoce
                                    ? 'Desativar a sua própria conta? Você será desconectado e não conseguirá entrar novamente.'
                                    : "Desativar o acesso de {$u->nome}? Ele não conseguirá mais entrar no sistema.";
                                ?>
                                <form method="post" data-confirmar="<?= $v->e($pergunta) ?>">
                                    <?= $v->csrfCampo() ?>
                                    <input type="hidden" name="acao" value="situacao">
                                    <input type="hidden" name="usuario_id" value="<?= (int) $u->id ?>">
                                    <input type="hidden" name="ativo" value="0">
                                    <button type="submit" class="botao botao--pequeno botao--perigo"
                                        <?= $ultimoAdmin ? 'disabled title="Último administrador ativo: não pode ser desativado"' : '' ?>>
                                        <?= $v->icone('inativar') ?> Desativar
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post">
                                    <?= $v->csrfCampo() ?>
                                    <input type="hidden" name="acao" value="situacao">
                                    <input type="hidden" name="usuario_id" value="<?= (int) $u->id ?>">
                                    <input type="hidden" name="ativo" value="1">
                                    <button type="submit" class="botao botao--pequeno"><?= $v->icone('mais') ?> Ativar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

</main>

<dialog class="dialogo modal__caixa dialogo--largo" id="modal-usuario" aria-labelledby="modal-usuario-titulo"
    data-editando="<?= $editando ? '1' : '' ?>" <?= $abrirFormulario ? 'data-abrir' : '' ?>>
    <form method="post" id="formulario">
        <?= $v->csrfCampo() ?>
        <div class="modal__cabecalho">
            <h2 id="modal-usuario-titulo"><?= $editando ? 'Editar ' . $v->e($valor('usuario')) : 'Novo usuário' ?></h2>
            <button type="button" class="botao botao--fantasma botao--icone" data-fechar-modal aria-label="Fechar"><?= $v->icone('fechar') ?></button>
        </div>
        <div class="modal__corpo dialogo__corpo">
            <?php if ($erroDoFormulario !== null): ?>
                <div class="aviso aviso--erro" role="alert"><?= $v->e($erroDoFormulario) ?></div>
            <?php endif; ?>
            <div class="grade grade--2">
                <div class="campo">
                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome" value="<?= $v->e($valor('nome')) ?>" required autofocus>
                </div>
                <div class="campo">
                    <label for="usuario">Usuário (login)</label>
                    <input type="text" id="usuario" name="usuario" value="<?= $v->e($valor('usuario')) ?>" autocomplete="off" required <?= $editando ? 'readonly' : '' ?>>
                </div>
                <div class="campo">
                    <label for="senha"><?= $editando ? 'Nova senha' : 'Senha' ?></label>
                    <input type="password" id="senha" name="senha" minlength="8" autocomplete="new-password" required>
                    <small><?= $editando ? 'Obrigatória ao salvar: a senha atual não pode ser recuperada.' : 'Mínimo de 8 caracteres.' ?></small>
                </div>
                <div class="campo">
                    <label for="tipo">Perfil</label>
                    <select id="tipo" name="tipo">
                        <option value="bc" <?= $valor('tipo') !== 'admin' ? 'selected' : '' ?>>Bombeiro — vê o próprio portal</option>
                        <option value="admin" <?= $valor('tipo') === 'admin' ? 'selected' : '' ?>>Administrador — gerencia a escala</option>
                    </select>
                </div>
                <div class="campo ocupa-tudo">
                    <label for="bombeiro_id">Bombeiro vinculado</label>
                    <select id="bombeiro_id" name="bombeiro_id">
                        <option value="">Nenhum (somente administração)</option>
                        <?php foreach ($bombeirosAtivos as $b): ?>
                            <option value="<?= (int) $b->id ?>" <?= $bombeiroSelecionado === $b->id ? 'selected' : '' ?>><?= $v->e($b->nomeCompleto) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>O portal mostra os plantões e valores deste bombeiro.</small>
                </div>
                <div class="ocupa-tudo form-linha">
                    <label class="marcador"><input type="checkbox" name="ativo" <?= isset($form['ativo']) ? 'checked' : '' ?>> Ativo</label>
                    <?php if ($editando): ?>
                        <input type="hidden" name="force" value="on">
                    <?php else: ?>
                        <label class="marcador"><input type="checkbox" name="force" <?= isset($form['force']) ? 'checked' : '' ?>> Substituir se o usuário já existir</label>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="rodape-form">
            <button type="button" class="botao" data-fechar-modal>Cancelar</button>
            <button type="submit" class="botao botao--primario"><?= $editando ? 'Salvar alterações' : 'Criar usuário' ?></button>
        </div>
    </form>
</dialog>

<script>
(function () {
    const dialogo = document.getElementById('modal-usuario');

    // fora da edição o formulário já está vazio: abre sem recarregar. Na edição, o link
    // recarrega a página para trazer o formulário de "novo" limpo.
    document.querySelector('[data-novo-usuario]').addEventListener('click', (evento) => {
        if (dialogo.dataset.editando) return;
        evento.preventDefault();
        dialogo.showModal();
    });

    dialogo.querySelectorAll('[data-fechar-modal]').forEach((botao) => botao.addEventListener('click', () => dialogo.close()));
    dialogo.addEventListener('click', (evento) => { if (evento.target === dialogo) dialogo.close(); });

    // ao fechar, tira ?editar / ?novo da URL para um F5 não reabrir o modal
    dialogo.addEventListener('close', () => {
        if (location.search) history.replaceState(null, '', location.pathname);
    });

    if (dialogo.hasAttribute('data-abrir')) dialogo.showModal();
})();
</script>

<?php require __DIR__ . '/_confirmacao.php'; ?>
<script src="<?= $v->recurso('js/script.js') ?>"></script>
</body>
</html>
