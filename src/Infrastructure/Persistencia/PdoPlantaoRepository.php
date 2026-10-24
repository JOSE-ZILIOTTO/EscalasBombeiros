<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\Plantao;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Escala\Turno;
use Escala\Domain\Excecao\PlantaoDuplicado;
use PDO;
use PDOException;

final class PdoPlantaoRepository implements PlantaoRepository
{
    private const SELECT = "SELECT p.id, p.bombeiro_id, p.data, p.turno, b.nome_completo, b.tipo
        FROM plantoes p JOIN bombeiros b ON b.id = p.bombeiro_id";
    private const ORDEM = "ORDER BY p.data, FIELD(p.turno, 'I', 'D', 'N', 'I_SUB'), b.nome_completo";

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function doDia(DataEscala $data, bool $bloquearParaEscrita = false): array
    {
        $sql = self::SELECT . ' WHERE p.data = ? ' . self::ORDEM . ($bloquearParaEscrita ? ' FOR UPDATE' : '');
        return $this->consultar($sql, [$data->emTexto()]);
    }

    public function doPeriodo(DataEscala $inicio, DataEscala $fim): array
    {
        return $this->consultar(self::SELECT . ' WHERE p.data BETWEEN ? AND ? ' . self::ORDEM, [$inicio->emTexto(), $fim->emTexto()]);
    }

    public function doBombeiro(int $bombeiroId, DataEscala $inicio, DataEscala $fim): array
    {
        return $this->consultar(
            self::SELECT . ' WHERE p.bombeiro_id = ? AND p.data BETWEEN ? AND ? ' . self::ORDEM,
            [(string) $bombeiroId, $inicio->emTexto(), $fim->emTexto()],
        );
    }

    public function inserir(int $bombeiroId, DataEscala $data, Turno $turno): int
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO plantoes (bombeiro_id, data, turno) VALUES (?, ?, ?)');
            $stmt->execute([$bombeiroId, $data->emTexto(), $turno->value]);
        } catch (PDOException $erro) {
            if (ErroDeChaveUnica::ehViolacao($erro)) {
                throw new PlantaoDuplicado();
            }
            throw $erro;
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function remover(int $plantaoId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM plantoes WHERE id = ?');
        $stmt->execute([$plantaoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @param list<string> $parametros
     * @return list<Plantao>
     */
    private function consultar(string $sql, array $parametros): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        $plantoes = [];
        foreach ($stmt->fetchAll() as $l) {
            $turno = Turno::tryFrom((string) $l['turno']);
            if ($turno === null) {
                continue; // turno gravado vazio por versões antigas (ver migração 001): ignorado
            }
            $plantoes[] = new Plantao(
                (int) $l['id'],
                (int) $l['bombeiro_id'],
                (string) $l['nome_completo'],
                TipoBombeiro::from((string) $l['tipo']),
                DataEscala::deTexto((string) $l['data']),
                $turno,
            );
        }
        return $plantoes;
    }
}
