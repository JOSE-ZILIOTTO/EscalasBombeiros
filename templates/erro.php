<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var string $mensagem
 */
$titulo = 'Não foi possível concluir';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require __DIR__ . '/_cabeca.php'; ?>
</head>
<body>
<main class="login">
    <div class="login__caixa">
        <div class="cartao cartao__corpo">
            <h1>Não foi possível concluir</h1>
            <div class="aviso aviso--erro espaco-acima" role="alert"><?= $v->e($mensagem) ?></div>
            <div class="form-linha">
                <a class="botao" href="javascript:history.back()"><?= $v->icone('voltar') ?> Voltar</a>
                <a class="botao botao--primario" href="index.php">Ir para o início</a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
