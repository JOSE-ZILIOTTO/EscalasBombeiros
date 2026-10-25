<?php

declare(strict_types=1);

/**
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var string $erro
 */
$titulo = 'Entrar';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require __DIR__ . '/_cabeca.php'; ?>
</head>
<body>
<main class="login">
    <div class="login__caixa">
        <div class="login__marca">
            <img src="<?= $v->recurso('img/logo.png') ?>" alt="">
            <div>
                <h1>Escala de Plantões</h1>
                <p>Acesse com seu usuário e senha</p>
            </div>
        </div>

        <div class="cartao cartao__corpo">
            <?php if ($erro !== ''): ?>
                <div class="aviso aviso--erro" role="alert"><?= $v->e($erro) ?></div>
            <?php endif; ?>

            <form id="loginForm" method="post" novalidate>
                <?= $v->csrfCampo() ?>
                <div class="campo">
                    <label for="user">Usuário</label>
                    <input id="user" name="user" type="text" autocomplete="username" required autofocus>
                </div>

                <div class="campo">
                    <label for="pass">Senha</label>
                    <div class="login__senha">
                        <input id="pass" name="pass" type="password" autocomplete="current-password" required>
                        <button type="button" class="botao botao--fantasma botao--pequeno" id="mostrarSenha" aria-pressed="false">Mostrar</button>
                    </div>
                    <span class="login__aviso-caps" id="avisoCaps" hidden>Caps Lock está ativado</span>
                </div>

                <label class="marcador"><input id="remember" type="checkbox"> Lembrar meu usuário neste computador</label>

                <button class="botao botao--primario botao--bloco" type="submit">Entrar</button>
            </form>
        </div>

        <p class="login__rodape">Esqueceu a senha? Peça ao administrador do sistema para redefini-la.</p>
        <p class="login__rodape"><a href="privacidade.php">Aviso de privacidade</a></p>
    </div>
</main>

<script>
(function () {
    const usuario = document.getElementById('user');
    const senha = document.getElementById('pass');
    const lembrar = document.getElementById('remember');
    const mostrar = document.getElementById('mostrarSenha');

    // só o usuário é lembrado; senhas salvas por versões antigas são apagadas
    try {
        localStorage.removeItem('senha');
        if (localStorage.getItem('usuario')) {
            usuario.value = localStorage.getItem('usuario');
            lembrar.checked = true;
            senha.focus();
        }
    } catch (e) { /* armazenamento bloqueado: segue sem lembrar */ }

    document.getElementById('loginForm').addEventListener('submit', () => {
        try {
            if (lembrar.checked) { localStorage.setItem('usuario', usuario.value); }
            else { localStorage.removeItem('usuario'); }
            localStorage.removeItem('lembrar');
        } catch (e) { /* idem */ }
    });

    mostrar.addEventListener('click', () => {
        const visivel = senha.type === 'password';
        senha.type = visivel ? 'text' : 'password';
        mostrar.textContent = visivel ? 'Ocultar' : 'Mostrar';
        mostrar.setAttribute('aria-pressed', String(visivel));
    });

    senha.addEventListener('keyup', (e) => {
        document.getElementById('avisoCaps').hidden = !(e.getModifierState && e.getModifierState('CapsLock'));
    });
})();
</script>
</body>
</html>
