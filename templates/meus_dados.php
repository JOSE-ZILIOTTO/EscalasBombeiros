<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var \Escala\Application\Portal\MeusDados $dados
 */
$titulo = 'Meus dados';
$ativo = 'meus_dados';
$usuario = $dados->usuario;
$bombeiro = $dados->bombeiro;
$d = $bombeiro?->dados;
$ou = static fn (?string $valor): string => $valor === null || trim($valor) === '' ? '—' : $valor;
$endereco = $d === null ? '' : trim(implode(', ', array_filter([
    trim(($d->enderecoRua ?? '') . ' ' . ($d->enderecoNumero ?? '')),
    $d->enderecoBairro,
    trim(($d->enderecoCidade ?? '') . ($d->enderecoUf !== null ? '/' . $d->enderecoUf : '')),
    $d->enderecoCep,
], static fn (?string $parte): bool => $parte !== null && trim($parte) !== '')));
$uniforme = $d === null ? '' : implode(' · ', array_filter([
    $d->tamanhoGandola !== null ? 'Gandola ' . $d->tamanhoGandola : null,
    $d->tamanhoCamiseta !== null ? 'Camiseta ' . $d->tamanhoCamiseta : null,
    $d->tamanhoCalca !== null ? 'Calça ' . $d->tamanhoCalca : null,
    $d->tamanhoCalcado !== null ? 'Calçado ' . $d->tamanhoCalcado : null,
]));
$temSigilosos = $d?->cpf !== null || $d?->dadosBancarios !== null;
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
            <h1>Meus dados</h1>
            <p>Tudo o que o sistema guarda sobre você. Seus dados só são usados para montar a escala e pagar os plantões.</p>
        </div>
        <a class="botao botao--primario" href="meus_dados.php?formato=json"><?= $v->icone('baixar') ?> Baixar cópia (JSON)</a>
    </div>

    <section class="cartao">
        <div class="cartao__titulo"><h2>Acesso ao sistema</h2></div>
        <dl class="dados cartao__corpo">
            <dt>Nome</dt><dd><?= $v->e($usuario->nome) ?></dd>
            <dt>Login</dt><dd><?= $v->e($usuario->login) ?></dd>
            <dt>Perfil</dt><dd><?= $usuario->ehAdmin() ? 'Administrador' : 'Bombeiro' ?></dd>
            <dt>Senha</dt><dd class="mudo">Guardada só como código irreversível (hash): ninguém consegue lê-la.</dd>
        </dl>
    </section>

    <section class="cartao">
        <div class="cartao__titulo"><h2>Cadastro de bombeiro</h2></div>
        <?php if ($bombeiro === null): ?>
            <p class="cartao__corpo mudo">Seu login não está ligado a um cadastro de bombeiro, então não há outros dados seus no sistema.</p>
        <?php elseif ($bombeiro->anonimizado): ?>
            <p class="cartao__corpo mudo">Seus dados pessoais foram apagados. Só o nome <b><?= $v->e($bombeiro->nomeCompleto) ?></b> continua, no histórico de plantões.</p>
        <?php else: ?>
            <dl class="dados cartao__corpo">
                <dt>Nome completo</dt><dd><?= $v->e($bombeiro->nomeCompleto) ?></dd>
                <dt>Nome de guerra</dt><dd><?= $v->e($ou($bombeiro->nomeGuerra)) ?></dd>
                <dt>Tipo</dt><dd><?= $bombeiro->ehFixo() ? 'Fixo (efetivo)' : 'BC (voluntário)' ?></dd>
                <dt>CPF</dt><dd><?= $v->e($d->cpf?->mascarado() ?? '—') ?></dd>
                <dt>Telefone</dt><dd><?= $v->e($ou($d->telefonePrincipal)) ?></dd>
                <dt>Contato de emergência</dt><dd><?= $v->e($ou(trim(($d->contatoEmergenciaNome ?? '') . ' ' . ($d->contatoEmergenciaFone ?? '')))) ?></dd>
                <dt>Endereço</dt><dd><?= $v->e($ou($endereco)) ?></dd>
                <dt>Uniforme</dt><dd><?= $v->e($ou($uniforme)) ?></dd>
                <dt>Dados bancários</dt><dd><?= $d->dadosBancarios === null ? '—' : 'cadastrados' ?></dd>
            </dl>
            <?php if ($temSigilosos): ?>
                <details class="dados__sigilosos">
                    <summary>Mostrar CPF e dados bancários completos</summary>
                    <dl class="dados">
                        <dt>CPF</dt><dd><?= $v->e($d->cpf?->formatado ?? '—') ?></dd>
                        <dt>Dados bancários</dt><dd class="dados__texto"><?= $v->e($ou($d->dadosBancarios)) ?></dd>
                    </dl>
                </details>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if ($bombeiro !== null): ?>
        <section class="cartao">
            <div class="cartao__corpo">
                <p><b><?= count($dados->plantoes) ?></b> plantões registrados em seu nome (estão todos na cópia em JSON).</p>
            </div>
        </section>
    <?php endif; ?>

    <p class="nota-valores">
        Algum dado errado, ou quer que seus dados sejam apagados? Fale com o administrador do sistema.
        Veja o <a href="privacidade.php">aviso de privacidade</a> para saber como seus dados são tratados.
    </p>
</main>
</body>
</html>
