<?php

declare(strict_types=1);

/**
 * Barra superior. Incluída com require pelos templates.
 *
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var string $ativo  item atual: escala | bombeiros | usuarios | portal | meus_dados
 */
$ehPortal = in_array($ativo, ['portal', 'meus_dados'], true);
$itens = $ehPortal
    ? [
        'portal' => ['portal_usuario.php', 'calendario', 'Minha escala'],
        'meus_dados' => ['meus_dados.php', 'escudo', 'Meus dados'],
    ]
    : [
        'escala' => ['escala.php', 'calendario', 'Escala'],
        'bombeiros' => ['bombeiros.php', 'pessoas', 'Bombeiros'],
        'usuarios' => ['gera_hash.php', 'chave', 'Usuários'],
    ];
?>
<header class="topo">
    <a class="topo__marca" href="<?= $v->e(reset($itens)[0]) ?>">
        <img src="<?= $v->recurso('img/logo.png') ?>" alt="">
        <span>Escala de Plantões</span>
    </a>
    <nav class="topo__nav" aria-label="Principal" data-tutorial="nav">
        <?php foreach ($itens as $chave => [$href, $icone, $rotulo]): ?>
            <a href="<?= $v->e($href) ?>" data-tutorial="nav-<?= $chave ?>" <?= $chave === $ativo ? 'aria-current="page"' : '' ?>>
                <?= $v->icone($icone) ?><span><?= $v->e($rotulo) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="topo__acoes" data-tutorial="acoes">
        <?php if (!$ehPortal): ?>
            <a class="botao botao--fantasma botao--icone" href="escala.php?tutorial=1" data-abrir-tutorial aria-label="Ver o tutorial" title="Como usar o sistema">
                <?= $v->icone('ajuda') ?>
            </a>
        <?php endif; ?>
        <button type="button" class="botao botao--fantasma botao--icone" data-alternar-tema aria-label="Alternar tema claro/escuro" title="Alternar tema">
            <?= $v->icone('lua') ?>
        </button>
        <a class="botao botao--fantasma" href="logout.php"><?= $v->icone('sair') ?><span>Sair</span></a>
    </div>
</header>
