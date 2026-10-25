<?php

declare(strict_types=1);

namespace Escala\Application\Portal;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Escala\Plantao;
use Escala\Domain\Usuario\Usuario;

final readonly class MeusDados
{
    /** @param list<Plantao> $plantoes todos os plantões registrados, de qualquer mês */
    public function __construct(
        public Usuario $usuario,
        public ?Bombeiro $bombeiro,
        public array $plantoes,
    ) {
    }

    /**
     * Cópia em formato aberto (JSON) para o titular levar consigo. A senha não entra:
     * só o hash existe, e ele não serve ao titular.
     *
     * @return array<string, mixed>
     */
    public function exportacao(string $geradoEm): array
    {
        $b = $this->bombeiro;
        $d = $b?->dados;
        return [
            'gerado_em' => $geradoEm,
            'acesso' => [
                'nome' => $this->usuario->nome,
                'login' => $this->usuario->login,
                'perfil' => $this->usuario->papel->value,
                'ativo' => $this->usuario->ativo,
            ],
            'cadastro' => $b === null ? null : [
                'nome_completo' => $b->nomeCompleto,
                'nome_guerra' => $b->nomeGuerra,
                'tipo' => $b->tipo->value,
                'ativo' => $b->ativo,
                'cpf' => $d?->cpf?->formatado,
                'telefone' => $d?->telefonePrincipal,
                'contato_emergencia' => ['nome' => $d?->contatoEmergenciaNome, 'telefone' => $d?->contatoEmergenciaFone],
                'endereco' => [
                    'rua' => $d?->enderecoRua,
                    'numero' => $d?->enderecoNumero,
                    'bairro' => $d?->enderecoBairro,
                    'cidade' => $d?->enderecoCidade,
                    'uf' => $d?->enderecoUf,
                    'cep' => $d?->enderecoCep,
                ],
                'dados_bancarios' => $d?->dadosBancarios,
                'uniforme' => [
                    'gandola' => $d?->tamanhoGandola,
                    'camiseta' => $d?->tamanhoCamiseta,
                    'calca' => $d?->tamanhoCalca,
                    'calcado' => $d?->tamanhoCalcado,
                ],
            ],
            'plantoes' => array_map(static fn (Plantao $p): array => [
                'data' => $p->data->emTexto(),
                'turno' => $p->turno->value,
                'horas' => $p->turno->horas(),
            ], $this->plantoes),
        ];
    }
}
