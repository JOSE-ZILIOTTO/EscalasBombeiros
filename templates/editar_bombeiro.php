<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var int $id
 * @var array<string, string|bool|null> $b valores do formulário (do banco ou reenviados após erro)
 * @var array<string, string|null> $mascaras CPF e dados bancários mascarados (null = não cadastrado)
 * @var bool $anonimizado
 * @var array{tipo: string, mensagem: string}|null $flash
 */
$titulo = 'Editar bombeiro';
$ativo = 'bombeiros';
$comCsrf = true; // o botão "Mostrar" dos campos sigilosos chama a API
$valor = static fn (string $campo): string => (string) ($b[$campo] ?? '');
$atributosHtml = static function (array $extras) use ($v): string {
    $atributos = '';
    foreach ($extras as $atributo => $conteudo) {
        $atributos .= ' ' . $atributo . '="' . $v->e($conteudo) . '"';
    }
    return $atributos;
};
$campoTexto = static function (string $nome, string $rotulo, array $extras = []) use ($v, $valor, $atributosHtml): string {
    return '<div class="campo"><label for="' . $nome . '">' . $v->e($rotulo) . '</label>'
        . '<input type="text" id="' . $nome . '" name="' . $nome . '" value="' . $v->e($valor($nome)) . '"' . $atributosHtml($extras) . '></div>';
};
/*
 * CPF e dados bancários: o valor real não vem no HTML. Com valor cadastrado, a tela mostra
 * a máscara; "Mostrar" busca o valor na API e "Alterar" abre o campo (sem abrir, não é
 * enviado e o valor gravado é mantido). Sem valor cadastrado, o campo já vem aberto.
 */
$campoSigiloso = static function (string $nome, string $rotulo, bool $multilinha, array $extras = []) use ($v, $b, $mascaras, $id, $atributosHtml): string {
    $aberto = $b[$nome] !== null || $mascaras[$nome] === null;
    $digitado = $v->e((string) $b[$nome]);
    $atributos = ' id="' . $nome . '" name="' . $nome . '"' . $atributosHtml($extras) . ($aberto ? '' : ' hidden disabled');
    $entrada = $multilinha ? "<textarea{$atributos}>{$digitado}</textarea>" : "<input type=\"text\"{$atributos} value=\"{$digitado}\">";

    $html = '<div class="campo sigiloso" data-sigiloso="' . $nome . '" data-bombeiro-id="' . $id . '"><label for="' . $nome . '">' . $v->e($rotulo) . '</label>';
    if (!$aberto) {
        $html .= '<div class="sigiloso__atual">'
            . '<span class="sigiloso__valor" data-sigiloso-valor data-mascara-exibida="' . $v->e($mascaras[$nome]) . '">' . $v->e($mascaras[$nome]) . '</span>'
            . '<button type="button" class="botao botao--pequeno botao--fantasma" data-sigiloso-mostrar>' . $v->icone('olho') . ' <span>Mostrar</span></button>'
            . '<button type="button" class="botao botao--pequeno" data-sigiloso-alterar>' . $v->icone('editar') . ' Alterar</button>'
            . '</div>';
    }
    $dica = $aberto ? 'Guardado com criptografia.' : 'Guardado com criptografia. Para apagar, clique em Alterar e deixe em branco.';
    return $html . $entrada . '<small>' . $dica . '</small></div>';
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
    <?php if ($anonimizado): ?>
        <div class="aviso aviso--neutro" role="status">Os dados pessoais deste bombeiro foram apagados (LGPD). Só o nome continua, para o histórico de plantões.</div>
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
                    <?= $campoSigiloso('cpf', 'CPF', false, ['data-mascara' => 'cpf', 'placeholder' => '000.000.000-00', 'inputmode' => 'numeric']) ?>
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
                <?= $campoSigiloso('dados_bancarios', 'Banco, agência, conta e titular', true) ?>
            </section>
        </div>

        <div class="rodape-form">
            <a class="botao" href="bombeiros.php">Cancelar</a>
            <button type="submit" class="botao botao--primario">Salvar alterações</button>
        </div>
    </form>
</main>

<div id="toast-container" aria-live="polite"></div>
<script src="<?= $v->recurso('js/script.js') ?>"></script>
</body>
</html>
