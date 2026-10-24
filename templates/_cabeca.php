<?php

declare(strict_types=1);

/**
 * <head> comum. Incluído pelos templates com require; usa as variáveis de quem incluiu.
 *
 * @var \Escala\Http\View\AjudanteDeView $v
 * @var string $titulo
 * @var bool|null $comCsrf publica o token para o fetch do script.js
 */
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $v->e($titulo) ?> · Escala de Plantões</title>
<?php if (!empty($comCsrf)): ?>
<meta name="csrf-token" content="<?= $v->e($v->csrfToken()) ?>">
<?php endif; ?>
<?php // link explícito: sem ele alguns navegadores pegam o /favicon.ico da raiz do servidor (o do XAMPP) ?>
<link rel="icon" href="<?= $v->recurso('favicon.ico') ?>" sizes="48x48">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $v->recurso('favicon-32x32.png') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $v->recurso('favicon-16x16.png') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $v->recurso('apple-touch-icon.png') ?>">
<link rel="stylesheet" href="<?= $v->recurso('css/app.css') ?>">
<script src="<?= $v->recurso('js/tema.js') ?>"></script>
