<?php

declare(strict_types=1);

namespace Escala\Infrastructure\Persistencia;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\Cpf;
use Escala\Domain\Bombeiro\DadosCadastrais;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ReferenciaCicloFixo;
use Escala\Domain\Excecao\DadoInvalido;
use Escala\Infrastructure\Seguranca\CifraDeDados;
use PDO;
use RuntimeException;

final class PdoBombeiroRepository implements BombeiroRepository
{
    private const COLUNAS = 'id, nome_completo, nome_guerra, cpf, tipo, ativo, fixo_ref_data, fixo_ref_dia_ciclo,
        endereco_rua, endereco_numero, endereco_bairro, endereco_cidade, endereco_uf, endereco_cep,
        telefone_principal, contato_emergencia_nome, contato_emergencia_fone, dados_bancarios,
        tamanho_gandola, tamanho_camiseta, tamanho_calca, tamanho_calcado, inativado_em, anonimizado_em';

    /** @param CifraDeDados $cifra CPF e dados bancários são gravados criptografados */
    public function __construct(private readonly PDO $pdo, private readonly CifraDeDados $cifra)
    {
    }

    public function buscar(int $id): ?Bombeiro
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUNAS . ' FROM bombeiros WHERE id = ?');
        $stmt->execute([$id]);
        $linha = $stmt->fetch();
        return $linha === false ? null : $this->hidratar($linha);
    }

    public function todos(): array
    {
        return $this->listar('1 = 1', 'nome_completo');
    }

    public function ativos(): array
    {
        return $this->listar('ativo = 1', 'nome_completo');
    }

    public function fixosAtivos(): array
    {
        return $this->listar("ativo = 1 AND tipo = 'Fixo'", 'id');
    }

    public function bcsAtivos(): array
    {
        return $this->listar("ativo = 1 AND tipo = 'BC'", 'id');
    }

    public function inserir(Bombeiro $bombeiro): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO bombeiros (nome_completo, nome_guerra, cpf, tipo, ativo, fixo_ref_data, fixo_ref_dia_ciclo,
                endereco_rua, endereco_numero, endereco_bairro, endereco_cidade, endereco_uf, endereco_cep,
                telefone_principal, contato_emergencia_nome, contato_emergencia_fone, dados_bancarios,
                tamanho_gandola, tamanho_camiseta, tamanho_calca, tamanho_calcado)
             VALUES (:nome_completo, :nome_guerra, :cpf, :tipo, :ativo, :fixo_ref_data, :fixo_ref_dia_ciclo,
                :endereco_rua, :endereco_numero, :endereco_bairro, :endereco_cidade, :endereco_uf, :endereco_cep,
                :telefone_principal, :contato_emergencia_nome, :contato_emergencia_fone, :dados_bancarios,
                :tamanho_gandola, :tamanho_camiseta, :tamanho_calca, :tamanho_calcado)'
        );
        $stmt->execute($this->parametros($bombeiro));
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(Bombeiro $bombeiro): void
    {
        if ($bombeiro->id === null) {
            throw new RuntimeException('Não é possível atualizar um bombeiro sem id.');
        }
        $stmt = $this->pdo->prepare(
            'UPDATE bombeiros SET
                nome_completo = :nome_completo, nome_guerra = :nome_guerra, cpf = :cpf, tipo = :tipo, ativo = :ativo,
                fixo_ref_data = :fixo_ref_data, fixo_ref_dia_ciclo = :fixo_ref_dia_ciclo,
                endereco_rua = :endereco_rua, endereco_numero = :endereco_numero, endereco_bairro = :endereco_bairro,
                endereco_cidade = :endereco_cidade, endereco_uf = :endereco_uf, endereco_cep = :endereco_cep,
                telefone_principal = :telefone_principal, contato_emergencia_nome = :contato_emergencia_nome,
                contato_emergencia_fone = :contato_emergencia_fone, dados_bancarios = :dados_bancarios,
                tamanho_gandola = :tamanho_gandola, tamanho_camiseta = :tamanho_camiseta,
                tamanho_calca = :tamanho_calca, tamanho_calcado = :tamanho_calcado,
                inativado_em = CASE WHEN :inativo = 1 THEN COALESCE(inativado_em, CURRENT_DATE) END,
                anonimizado_em = CASE WHEN :anonimizado = 1 THEN COALESCE(anonimizado_em, NOW()) ELSE anonimizado_em END
             WHERE id = :id'
        );
        $stmt->execute([
            ...$this->parametros($bombeiro),
            'inativo' => $bombeiro->ativo ? 0 : 1,
            'anonimizado' => $bombeiro->anonimizado ? 1 : 0,
            'id' => $bombeiro->id,
        ]);
    }

    /**
     * Criptografa CPF e dados bancários gravados antes da criptografia existir.
     * Usado só por bin/proteger_dados_antigos.php; pode rodar mais de uma vez.
     *
     * @return int quantos cadastros foram regravados
     */
    public function protegerDadosAntigos(): int
    {
        $linhas = $this->pdo->query('SELECT id, cpf, dados_bancarios FROM bombeiros')->fetchAll();
        $stmt = $this->pdo->prepare('UPDATE bombeiros SET cpf = :cpf, dados_bancarios = :dados_bancarios WHERE id = :id');
        $regravados = 0;
        foreach ($linhas as $l) {
            $antigos = array_filter(
                [$l['cpf'], $l['dados_bancarios']],
                fn (mixed $v): bool => is_string($v) && $v !== '' && !$this->cifra->estaCifrado($v),
            );
            if ($antigos === []) {
                continue;
            }
            $stmt->execute([
                'cpf' => $this->cifra->cifrar($this->cifra->decifrar($l['cpf'])),
                'dados_bancarios' => $this->cifra->cifrar($this->cifra->decifrar($l['dados_bancarios'])),
                'id' => (int) $l['id'],
            ]);
            $regravados++;
        }
        return $regravados;
    }

    /** @return list<Bombeiro> */
    private function listar(string $filtro, string $ordem): array
    {
        // $filtro e $ordem são constantes internas desta classe, nunca vindas do usuário
        $linhas = $this->pdo->query('SELECT ' . self::COLUNAS . " FROM bombeiros WHERE {$filtro} ORDER BY {$ordem}")->fetchAll();
        return array_map($this->hidratar(...), $linhas);
    }

    /** @param array<string, mixed> $l */
    private function hidratar(array $l): Bombeiro
    {
        $cpf = $this->cifra->decifrar($l['cpf']);
        return new Bombeiro(
            id: (int) $l['id'],
            nomeCompleto: (string) $l['nome_completo'],
            nomeGuerra: $l['nome_guerra'] !== null ? (string) $l['nome_guerra'] : null,
            tipo: TipoBombeiro::from((string) $l['tipo']),
            ativo: (int) $l['ativo'] === 1,
            referenciaCiclo: $this->referencia($l['fixo_ref_data'], $l['fixo_ref_dia_ciclo']),
            dados: new DadosCadastrais(
                cpf: $cpf !== null && $cpf !== '' ? Cpf::doBanco($cpf) : null,
                enderecoRua: $l['endereco_rua'],
                enderecoNumero: $l['endereco_numero'],
                enderecoBairro: $l['endereco_bairro'],
                enderecoCidade: $l['endereco_cidade'],
                enderecoUf: $l['endereco_uf'],
                enderecoCep: $l['endereco_cep'],
                telefonePrincipal: $l['telefone_principal'],
                contatoEmergenciaNome: $l['contato_emergencia_nome'],
                contatoEmergenciaFone: $l['contato_emergencia_fone'],
                dadosBancarios: $this->cifra->decifrar($l['dados_bancarios']),
                tamanhoGandola: $l['tamanho_gandola'],
                tamanhoCamiseta: $l['tamanho_camiseta'],
                tamanhoCalca: $l['tamanho_calca'],
                tamanhoCalcado: $l['tamanho_calcado'],
            ),
            inativadoEm: $l['inativado_em'] !== null ? DataEscala::deTexto(substr((string) $l['inativado_em'], 0, 10)) : null,
            anonimizado: $l['anonimizado_em'] !== null,
        );
    }

    private function referencia(mixed $data, mixed $dia): ?ReferenciaCicloFixo
    {
        if ($data === null || $dia === null) {
            return null;
        }
        try {
            return new ReferenciaCicloFixo(DataEscala::deTexto((string) $data), (int) $dia);
        } catch (DadoInvalido) {
            return null; // referência corrompida no banco: tratado como "sem ciclo", sem derrubar a tela
        }
    }

    /** @return array<string, string|int|null> */
    private function parametros(Bombeiro $b): array
    {
        $d = $b->dados;
        return [
            'nome_completo' => $b->nomeCompleto,
            'nome_guerra' => $b->nomeGuerra,
            'cpf' => $this->cifra->cifrar($d->cpf?->formatado),
            'tipo' => $b->tipo->value,
            'ativo' => $b->ativo ? 1 : 0,
            'fixo_ref_data' => $b->referenciaCiclo?->data->emTexto(),
            'fixo_ref_dia_ciclo' => $b->referenciaCiclo?->diaDoCiclo,
            'endereco_rua' => $d->enderecoRua,
            'endereco_numero' => $d->enderecoNumero,
            'endereco_bairro' => $d->enderecoBairro,
            'endereco_cidade' => $d->enderecoCidade,
            'endereco_uf' => $d->enderecoUf,
            'endereco_cep' => $d->enderecoCep,
            'telefone_principal' => $d->telefonePrincipal,
            'contato_emergencia_nome' => $d->contatoEmergenciaNome,
            'contato_emergencia_fone' => $d->contatoEmergenciaFone,
            'dados_bancarios' => $this->cifra->cifrar($d->dadosBancarios),
            'tamanho_gandola' => $d->tamanhoGandola,
            'tamanho_camiseta' => $d->tamanhoCamiseta,
            'tamanho_calca' => $d->tamanhoCalca,
            'tamanho_calcado' => $d->tamanhoCalcado,
        ];
    }
}
