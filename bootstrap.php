<?php

declare(strict_types=1);

use Escala\Http\Aplicacao;
use Escala\Http\TabelaDeRotas;
use Escala\Infrastructure\Config\CarregadorDeEnv;
use Escala\Infrastructure\ContainerDaAplicacao;

// Erros nunca vão para a tela; ficam no log do PHP.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    error_log('[escala] vendor/autoload.php ausente: rode "composer build-producao" e envie a pasta vendor/.');
    http_response_code(500);
    exit('Sistema em manutenção.');
}
require $autoload;

$ambiente = getenv();
$config = (new CarregadorDeEnv())->carregar(
    $ambiente['ESCALA_ENV_FILE'] ?? __DIR__ . '/.env',
    is_array($ambiente) ? $ambiente : [],
);

// X-Forwarded-Proto: hospedagens que terminam o HTTPS num proxy entregam a requisição em http
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443')
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

return new Aplicacao(
    new ContainerDaAplicacao($config, __DIR__ . '/templates', $https),
    new TabelaDeRotas(),
);
