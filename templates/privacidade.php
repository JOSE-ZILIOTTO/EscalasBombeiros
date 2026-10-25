<?php

declare(strict_types=1);

/**
 * Aviso de privacidade (LGPD art. 9): linguagem simples, sem login.
 *
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var string $controlador quem decide sobre os dados (PRIVACIDADE_CONTROLADOR)
 * @var string|null $contato onde o titular exerce os direitos (PRIVACIDADE_CONTATO)
 * @var int $anosDeRetencao
 */
$titulo = 'Aviso de privacidade';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php require __DIR__ . '/_cabeca.php'; ?>
</head>
<body>
<main class="pagina pagina--estreita">
    <div class="cabecalho">
        <div>
            <a class="cabecalho__voltar" href="index.php"><?= $v->icone('voltar') ?> Escala de Plantões</a>
            <h1>Aviso de privacidade</h1>
            <p>Como o sistema de escala trata os seus dados pessoais, conforme a Lei Geral de Proteção de Dados (Lei 13.709/2018).</p>
        </div>
    </div>

    <article class="cartao cartao__corpo texto-longo">
        <h2>Quem cuida dos dados</h2>
        <p>
            Responsável pelos dados (controlador): <b><?= $v->e($controlador) ?></b>, que decide como eles são usados.
            <?php if ($contato !== null): ?>
                Para dúvidas ou pedidos sobre os seus dados: <b><?= $v->e($contato) ?></b>.
            <?php else: ?>
                Para dúvidas ou pedidos sobre os seus dados, fale com o administrador do sistema.
            <?php endif; ?>
        </p>

        <h2>Quais dados guardamos</h2>
        <ul>
            <li><b>Cadastro:</b> nome, nome de guerra, CPF, telefone, contato de emergência, endereço, tamanhos de uniforme e dados bancários.</li>
            <li><b>Escala:</b> os plantões de cada pessoa (data e turno) e as ausências dos Fixos.</li>
            <li><b>Acesso:</b> nome, login e senha. A senha é guardada só como código irreversível (hash): ninguém consegue lê-la.</li>
            <li><b>Segurança:</b> o horário das tentativas de login erradas, ligado a um código gerado a partir do endereço IP (o IP em si não é gravado), para bloquear quem tenta adivinhar senhas.</li>
        </ul>

        <h2>Para que usamos</h2>
        <ul>
            <li>Montar e divulgar a escala mensal de plantões.</li>
            <li>Calcular e pagar os plantões (CPF e dados bancários).</li>
            <li>Entrar em contato com você e, em uma emergência durante o serviço, com o contato que você indicou.</li>
            <li>Fornecer o uniforme no tamanho certo.</li>
        </ul>
        <p>
            Não usamos os dados para nenhuma outra finalidade, não os vendemos e não fazemos propaganda.
            A base legal é a execução das atividades do serviço e dos pagamentos (LGPD, art. 7º, V),
            o cumprimento de obrigações legais, como a prestação de contas (art. 7º, II), e, para o contato
            de emergência, a proteção da vida (art. 7º, VII).
        </p>

        <h2>Quem vê os seus dados</h2>
        <ul>
            <li><b>Administradores do sistema:</b> todo o cadastro, para gerenciar a escala e os pagamentos.</li>
            <li><b>Você:</b> a sua escala, os seus valores e o seu cadastro, na página <a href="meus_dados.php">Meus dados</a>.</li>
            <li><b>Colegas:</b> só o nome e os turnos, na planilha mensal da escala.</li>
        </ul>
        <p>Os dados não são repassados a terceiros, a não ser quando uma lei ou autoridade exigir.</p>

        <h2>Como protegemos</h2>
        <ul>
            <li>Conexão criptografada (HTTPS) e acesso só com login e senha.</li>
            <li>CPF e dados bancários guardados com criptografia e mostrados mascarados nas telas.</li>
            <li>Cada pessoa só vê os próprios plantões e valores.</li>
        </ul>

        <h2>Por quanto tempo guardamos</h2>
        <p>
            Enquanto você estiver ativo na escala. Depois que você sair, os dados de cadastro ficam guardados
            por mais <?= $anosDeRetencao ?> anos, para prestação de contas dos pagamentos, e então são apagados.
            O seu nome continua no histórico de plantões, que faz parte do registro do serviço.
        </p>

        <h2>Seus direitos</h2>
        <p>Pela LGPD (art. 18), você pode:</p>
        <ul>
            <li><b>Ver</b> todos os dados que temos sobre você, em <a href="meus_dados.php">Meus dados</a>.</li>
            <li><b>Levar uma cópia</b> deles, pelo botão “Baixar cópia” na mesma página.</li>
            <li><b>Corrigir</b> dados errados ou desatualizados.</li>
            <li><b>Pedir que sejam apagados</b> quando você deixar o serviço, antes do fim do prazo acima, se não houver obrigação legal de mantê-los.</li>
            <li><b>Saber</b> com quem eles foram compartilhados.</li>
        </ul>
        <p>
            Para corrigir ou apagar, fale com <?= $contato !== null ? $v->e($contato) : 'o administrador do sistema' ?>.
            Se o seu pedido não for atendido, você pode reclamar à Autoridade Nacional de Proteção de Dados (ANPD), em gov.br/anpd.
        </p>

        <h2>Cookies e armazenamento no navegador</h2>
        <p>
            O sistema usa só um cookie de sessão, necessário para manter você conectado, e apagado ao sair ou fechar o navegador.
            No seu navegador ficam apenas a escolha de tema (claro ou escuro) e, se você marcar “lembrar”, o seu login (nunca a senha).
        </p>
    </article>
</main>
</body>
</html>
