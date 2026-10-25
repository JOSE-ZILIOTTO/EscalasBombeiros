<?php

declare(strict_types=1);

/**
 * Criptografa CPF e dados bancários gravados antes da criptografia (migração 002).
 * Uso: php bin/proteger_dados_antigos.php   — pode rodar mais de uma vez.
 * Antes: backup do banco e CHAVE_DADOS configurada no .env (guarde a chave em local seguro).
 */

use Escala\Infrastructure\Config\CarregadorDeEnv;
use Escala\Infrastructure\ContainerDaAplicacao;
use Escala\Infrastructure\Persistencia\PdoBombeiroRepository;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__);
require $raiz . '/vendor/autoload.php';

$ambiente = getenv();
$config = (new CarregadorDeEnv())->carregar($ambiente['ESCALA_ENV_FILE'] ?? $raiz . '/.env', is_array($ambiente) ? $ambiente : []);
$repositorio = (new ContainerDaAplicacao($config, $raiz . '/templates', false))->bombeiros();
if (!$repositorio instanceof PdoBombeiroRepository) {
    fwrite(STDERR, "Repositório inesperado.\n");
    exit(1);
}

$total = $repositorio->protegerDadosAntigos();
echo "Cadastros criptografados agora: {$total}\n";
