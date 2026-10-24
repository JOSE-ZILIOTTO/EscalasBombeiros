<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Domain\OrdemEscolha\EstadoOrdemEscolha;
use Escala\Domain\OrdemEscolha\OrdemEscolhaRepository;
use PDO;

/** Estado guardado como chave/valor na tabela configuracoes (mesmas chaves do sistema antigo). */
final class PdoOrdemEscolhaRepository implements OrdemEscolhaRepository
{
    private const ULTIMO_QUE_INICIOU = 'ultimo_bc_iniciou_mes';
    private const DA_VEZ = 'bc_da_vez_id';
    private const INICIO_SELECIONADO = 'bc_inicio_ordem_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function carregarEstado(): EstadoOrdemEscolha
    {
        $stmt = $this->pdo->prepare('SELECT chave, valor FROM configuracoes WHERE chave IN (?, ?, ?)');
        $stmt->execute([self::ULTIMO_QUE_INICIOU, self::DA_VEZ, self::INICIO_SELECIONADO]);
        $valores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return new EstadoOrdemEscolha(
            $this->id($valores[self::ULTIMO_QUE_INICIOU] ?? null),
            $this->id($valores[self::DA_VEZ] ?? null),
            $this->id($valores[self::INICIO_SELECIONADO] ?? null),
        );
    }

    public function salvarEstado(EstadoOrdemEscolha $estado): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach ([
            self::ULTIMO_QUE_INICIOU => $estado->ultimoQueIniciou,
            self::DA_VEZ => $estado->daVez,
            self::INICIO_SELECIONADO => $estado->inicioSelecionado,
        ] as $chave => $valor) {
            $stmt->execute([$chave, $valor === null ? null : (string) $valor]);
        }
    }

    private function id(mixed $valor): ?int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT);
        return $id === false ? null : $id;
    }
}
