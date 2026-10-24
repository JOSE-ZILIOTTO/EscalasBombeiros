<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Domain\Excecao\BombeiroJaVinculado;
use Escala\Domain\Usuario\PapelUsuario;
use Escala\Domain\Usuario\Usuario;
use Escala\Domain\Usuario\UsuarioRepository;
use PDO;
use PDOException;
use RuntimeException;

final class PdoUsuarioRepository implements UsuarioRepository
{
    private const COLUNAS = 'id, nome, usuario, senha_hash, tipo, ativo, bombeiro_id';
    private const INDICE_BOMBEIRO = 'uq_usuarios_bombeiro';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorId(int $id): ?Usuario
    {
        return $this->buscarUm('id = ?', $id);
    }

    public function buscarPorLogin(string $login): ?Usuario
    {
        return $this->buscarUm('usuario = ?', $login);
    }

    public function todos(): array
    {
        $linhas = $this->pdo->query('SELECT ' . self::COLUNAS . ' FROM usuarios ORDER BY nome, usuario')->fetchAll();
        return array_values(array_filter(array_map($this->hidratar(...), $linhas)));
    }

    public function contarAdminsAtivos(bool $bloquearParaEscrita = false): int
    {
        // FOR UPDATE não combina com COUNT(*) em todas as versões: trava as linhas e conta no PHP
        $sql = "SELECT id FROM usuarios WHERE tipo = 'admin' AND ativo = 1" . ($bloquearParaEscrita ? ' FOR UPDATE' : '');
        return count($this->pdo->query($sql)->fetchAll());
    }

    public function inserir(Usuario $usuario): int
    {
        $this->gravar(
            'INSERT INTO usuarios (nome, usuario, senha_hash, tipo, ativo, bombeiro_id)
             VALUES (:nome, :usuario, :senha_hash, :tipo, :ativo, :bombeiro_id)',
            $this->parametros($usuario),
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(Usuario $usuario): void
    {
        if ($usuario->id === null) {
            throw new RuntimeException('Não é possível atualizar um usuário sem id.');
        }
        $this->gravar(
            'UPDATE usuarios SET nome = :nome, usuario = :usuario, senha_hash = :senha_hash, tipo = :tipo,
                ativo = :ativo, bombeiro_id = :bombeiro_id WHERE id = :id',
            [...$this->parametros($usuario), 'id' => $usuario->id],
        );
    }

    private function buscarUm(string $filtro, int|string $valor): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUNAS . " FROM usuarios WHERE {$filtro} LIMIT 1");
        $stmt->execute([$valor]);
        $l = $stmt->fetch();
        return $l === false ? null : $this->hidratar($l);
    }

    /** @param array<string, mixed> $l */
    private function hidratar(array $l): ?Usuario
    {
        $papel = PapelUsuario::tryFrom((string) $l['tipo']);
        if ($papel === null) {
            return null; // papel desconhecido no banco: tratado como inexistente (não loga)
        }
        return new Usuario(
            (int) $l['id'],
            (string) $l['nome'],
            (string) $l['usuario'],
            (string) $l['senha_hash'],
            $papel,
            (int) $l['ativo'] === 1,
            $l['bombeiro_id'] !== null ? (int) $l['bombeiro_id'] : null,
        );
    }

    /** @param array<string, string|int|null> $parametros */
    private function gravar(string $sql, array $parametros): void
    {
        try {
            $this->pdo->prepare($sql)->execute($parametros);
        } catch (PDOException $erro) {
            if (ErroDeChaveUnica::ehViolacao($erro, self::INDICE_BOMBEIRO)) {
                throw new BombeiroJaVinculado();
            }
            throw $erro;
        }
    }

    /** @return array<string, string|int|null> */
    private function parametros(Usuario $u): array
    {
        return [
            'nome' => $u->nome,
            'usuario' => $u->login,
            'senha_hash' => $u->senhaHash,
            'tipo' => $u->papel->value,
            'ativo' => $u->ativo ? 1 : 0,
            'bombeiro_id' => $u->bombeiroId,
        ];
    }
}
