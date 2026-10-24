<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\ExcecoesDoPeriodo;
use PDO;

final class PdoExcecaoFixoRepository implements ExcecaoFixoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function doPeriodo(DataEscala $inicio, DataEscala $fim): ExcecoesDoPeriodo
    {
        $stmt = $this->pdo->prepare('SELECT bombeiro_id, data FROM excecoes_ciclo_fixo WHERE data BETWEEN ? AND ?');
        $stmt->execute([$inicio->emTexto(), $fim->emTexto()]);

        return new ExcecoesDoPeriodo(array_map(
            static fn (array $l): array => ['bombeiroId' => (int) $l['bombeiro_id'], 'data' => DataEscala::deTexto((string) $l['data'])],
            $stmt->fetchAll(),
        ));
    }

    public function registrar(int $bombeiroId, DataEscala $data): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO excecoes_ciclo_fixo (bombeiro_id, data) VALUES (?, ?)');
        $stmt->execute([$bombeiroId, $data->emTexto()]);
    }

    public function remover(int $bombeiroId, DataEscala $data): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM excecoes_ciclo_fixo WHERE bombeiro_id = ? AND data = ?');
        $stmt->execute([$bombeiroId, $data->emTexto()]);
    }
}
