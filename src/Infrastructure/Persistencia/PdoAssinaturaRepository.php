<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Domain\Planilha\AssinaturaDaPlanilha;
use Escala\Domain\Planilha\AssinaturaRepository;
use PDO;

/** Guardada como chave/valor na tabela configuracoes, ao lado do estado da ordem de escolha. */
final class PdoAssinaturaRepository implements AssinaturaRepository
{
    private const NOME = 'assinatura_nome';
    private const CARGO = 'assinatura_cargo';
    private const UNIDADE = 'assinatura_unidade';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function obter(): AssinaturaDaPlanilha
    {
        $stmt = $this->pdo->prepare('SELECT chave, valor FROM configuracoes WHERE chave IN (?, ?, ?)');
        $stmt->execute([self::NOME, self::CARGO, self::UNIDADE]);
        $valores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        if (!isset($valores[self::NOME], $valores[self::CARGO])) {
            return AssinaturaDaPlanilha::padrao();
        }
        return new AssinaturaDaPlanilha($valores[self::NOME], $valores[self::CARGO], (string) ($valores[self::UNIDADE] ?? ''));
    }

    public function salvar(AssinaturaDaPlanilha $assinatura): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach ([self::NOME => $assinatura->nome, self::CARGO => $assinatura->cargo, self::UNIDADE => $assinatura->unidade] as $chave => $valor) {
            $stmt->execute([$chave, $valor]);
        }
    }
}
