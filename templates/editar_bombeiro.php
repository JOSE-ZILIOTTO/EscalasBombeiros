<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var int $id
 * @var array<string, string|bool> $b valores do formulário (do banco ou reenviados após erro)
 * @var array{tipo: string, mensagem: string}|null $flash
 */
$titulo = 'Editar bombeiro';
$ativo = 'bombeiros';
$valor = static fn (string $campo): string => (string) ($b[$campo] ?? '');
$campoTexto = static function (string $nome, string $rotulo, array $extras = []) use ($v, $valor): string {
    $atributos = '';
    foreach ($extras as $atributo => $conteudo) {
        $atributos .= ' ' . $atributo . '="' . $v->e($conteudo) . '"';
    }
    return '<div class="campo"><label for="' . $nome . '">' . $v->e($rotulo) . '</label>'
        . '<input type="text" id="' . $nome . '" name="' . $nome . '" value="' . $v->e($valor($nome)) . '"' . $atributos . '></div>';
};
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
            <a class="cabecalho__voltar" href="bombeiros.php"><?= $v->icone('voltar') ?> Bombeiros</a>
            <h1><?= $v->e($valor('nome_completo') ?: 'Editar bombeiro') ?></h1>
        </div>
    </div>

    <?php if ($flash !== null): ?>
        <div class="aviso aviso--<?= $flash['tipo'] === 'success' ? 'sucesso' : 'erro' ?>" role="alert"><?= $v->e($flash['mensagem']) ?></div>
    <?php endif; ?>

    <form class="cartao" action="editar_bombeiro.php?id=<?= $id ?>" method="post">
        <?= $v->csrfCampo() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="cartao__corpo">
            <section class="secao">
                <h3>Identificação</h3>
                <div class="grade grade--2">
                    <?= $campoTexto('nome_completo', 'Nome completo', ['required' => 'required']) ?>
                    <div class="campo">
                        <label for="nome_guerra">Nome de guerra</label>
                        <input type="text" id="nome_guerra" name="nome_guerra" maxlength="60" value="<?= $v->e($valor('nome_guerra')) ?>">
                        <small>Destacado em negrito na planilha. Em branco, usa o último sobrenome.</small>
                    </div>
                    <?= $campoTexto('cpf', 'CPF', ['data-mascara' => 'cpf', 'placeholder' => '000.000.000-00', 'inputmode' => 'numeric']) ?>
                    <div class="campo">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" required>
                            <option value="BC" <?= $valor('tipo') === 'BC' ? 'selected' : '' ?>>BC (voluntário)</option>
                            <option value="Fixo" <?= $valor('tipo') === 'Fixo' ? 'selected' : '' ?>>Fixo (efetivo)</option>
                        </select>
                    </div>
                </div>

                <div id="fixo-fields" class="grade grade--2 espaco-acima">
                    <div class="campo">
                        <label for="fixo_ref_data">Data de referência do ciclo</label>
                        <input type="date" id="fixo_ref_data" name="fixo_ref_data" value="<?= $v->e($valor('fixo_ref_data')) ?>">
                    </div>
                    <div class="campo">
                        <label for="fixo_ref_dia_ciclo">Dia do ciclo nessa data</label>
                        <select id="fixo_ref_dia_ciclo" name="fixo_ref_dia_ciclo">
                            <option value="">Selecione…</option>
                            <?php foreach ([1 => 'Dia 1 — serviço', 2 => 'Dia 2 — folga', 3 => 'Dia 3 — folga', 4 => 'Dia 4 — folga'] as $dia => $rotulo): ?>
                                <option value="<?= $dia ?>" <?= $valor('fixo_ref_dia_ciclo') === (string) $dia ? 'selected' : '' ?>><?= $rotulo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="marcador espaco-acima">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?= !empty($b['ativo']) ? 'checked' : '' ?>> Ativo na escala
                </label>
            </section>

            <section class="secao">
                <h3>Contato</h3>
                <div class="grade grade--2">
                    <?= $campoTexto('telefone_principal', 'Telefone', ['data-mascara' => 'telefone', 'inputmode' => 'tel']) ?>
                    <div></div>
                    <?= $campoTexto('contato_emergencia_nome', 'Contato de emergência') ?>
                    <?= $campoTexto('contato_emergencia_fone', 'Telefone de emergência', ['data-mascara' => 'telefone', 'inputmode' => 'tel']) ?>
                </div>
            </section>

            <section class="secao">
                <h3>Endereço</h3>
                <div class="grade grade--4">
                    <div class="ocupa-2"><?= $campoTexto('endereco_rua', 'Rua') ?></div>
                    <?= $campoTexto('endereco_numero', 'Número') ?>
                    <?= $campoTexto('endereco_cep', 'CEP', ['data-mascara' => 'cep', 'inputmode' => 'numeric']) ?>
                    <div class="ocupa-2"><?= $campoTexto('endereco_bairro', 'Bairro') ?></div>
                    <?= $campoTexto('endereco_cidade', 'Cidade') ?>
                    <?= $campoTexto('endereco_uf', 'UF', ['maxlength' => '2']) ?>
                </div>
            </section>

            <section class="secao">
                <h3>Uniforme</h3>
                <div class="grade grade--4">
                    <?= $campoTexto('tamanho_gandola', 'Gandola') ?>
                    <?= $campoTexto('tamanho_camiseta', 'Camiseta') ?>
                    <?= $campoTexto('tamanho_calca', 'Calça') ?>
                    <?= $campoTexto('tamanho_calcado', 'Calçado') ?>
                </div>
            </section>

            <section class="secao">
                <h3>Dados bancários</h3>
                <div class="campo">
                    <label for="dados_bancarios">Banco, agência, conta e titular</label>
                    <textarea id="dados_bancarios" name="dados_bancarios"><?= $v->e($valor('dados_bancarios')) ?></textarea>
                </div>
            </section>
        </div>

        <div class="rodape-form">
            <a class="botao" href="bombeiros.php">Cancelar</a>
            <button type="submit" class="botao botao--primario">Salvar alterações</button>
        </div>
    </form>
</main>

<script src="<?= $v->recurso('js/script.js') ?>"></script>
</body>
</html>
