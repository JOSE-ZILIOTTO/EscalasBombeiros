<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Seguranca;

use InvalidArgumentException;
use RuntimeException;

/**
 * Criptografia em repouso dos dados pessoais mais sensíveis (CPF e dados bancários), com
 * AES-256-GCM: quem tiver só o banco (um dump ou backup vazado) não lê esses campos.
 * A chave fica no .env, nunca no banco nem no Git. Perder a chave = perder esses dados.
 *
 * Valores gravados antes da criptografia não têm o prefixo e são devolvidos como estão,
 * para o sistema continuar funcionando até rodar bin/proteger_dados_antigos.php.
 */
final class CifraDeDados
{
    private const PREFIXO = 'enc:v1:';
    private const ALGORITMO = 'aes-256-gcm';
    private const TAMANHO_IV = 12;
    private const TAMANHO_TAG = 16;

    private readonly string $chave;

    /** @param string $chaveBase64 32 bytes em base64 (CHAVE_DADOS no .env) */
    public function __construct(string $chaveBase64)
    {
        $chave = base64_decode($chaveBase64, true);
        if ($chave === false || strlen($chave) !== 32) {
            throw new InvalidArgumentException(
                'CHAVE_DADOS deve ter 32 bytes em base64. Gere com: php -r "echo base64_encode(random_bytes(32));"',
            );
        }
        $this->chave = $chave;
    }

    public function cifrar(?string $texto): ?string
    {
        if ($texto === null || $texto === '') {
            return $texto;
        }
        $iv = random_bytes(self::TAMANHO_IV);
        $tag = '';
        $cifrado = openssl_encrypt($texto, self::ALGORITMO, $this->chave, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAMANHO_TAG);
        if ($cifrado === false) {
            throw new RuntimeException('Falha ao criptografar dado pessoal.');
        }
        return self::PREFIXO . base64_encode($iv . $tag . $cifrado);
    }

    public function decifrar(?string $valor): ?string
    {
        if ($valor === null || !$this->estaCifrado($valor)) {
            return $valor;
        }
        $bruto = base64_decode(substr($valor, strlen(self::PREFIXO)), true);
        if ($bruto === false || strlen($bruto) < self::TAMANHO_IV + self::TAMANHO_TAG) {
            throw new RuntimeException('Dado pessoal criptografado está corrompido.');
        }
        $iv = substr($bruto, 0, self::TAMANHO_IV);
        $tag = substr($bruto, self::TAMANHO_IV, self::TAMANHO_TAG);
        $texto = openssl_decrypt(substr($bruto, self::TAMANHO_IV + self::TAMANHO_TAG), self::ALGORITMO, $this->chave, OPENSSL_RAW_DATA, $iv, $tag);
        if ($texto === false) {
            // GCM autentica: chave trocada ou valor adulterado caem aqui, nunca viram lixo silencioso
            throw new RuntimeException('Não foi possível descriptografar um dado pessoal: a CHAVE_DADOS mudou?');
        }
        return $texto;
    }

    public function estaCifrado(string $valor): bool
    {
        return str_starts_with($valor, self::PREFIXO);
    }
}
