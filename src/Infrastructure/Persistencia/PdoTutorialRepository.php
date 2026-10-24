<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Application\Relogio;
use Escala\Domain\Usuario\TutorialRepository;
use PDO;

/** Uma chave por usuário na tabela configuracoes (valor = quando viu), sem precisar de migração. */
final class PdoTutorialRepository implements TutorialRepository
{
    private const PREFIXO = 'tutorial_visto_usuario_';

    public function __construct(private readonly PDO $pdo, private readonly Relogio $relogio)
    {
    }

    public function foiVisto(int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM configuracoes WHERE chave = ?');
        $stmt->execute([self::PREFIXO . $usuarioId]);
        return $stmt->fetchColumn() !== false;
    }

    public function marcarComoVisto(int $usuarioId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        $stmt->execute([self::PREFIXO . $usuarioId, $this->relogio->agora()->format('Y-m-d H:i:s')]);
    }
}
