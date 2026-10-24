<?php

declare(strict_types=1);

namespace Escala\Application\Bombeiro;

use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Excecao\CampoObrigatorio;

/**
 * Cadastro rápido (nome, nome de guerra e tipo). A referência do ciclo de um Fixo é
 * preenchida depois, na edição — como no formulário antigo.
 */
final class CadastrarBombeiro
{
    public function __construct(private readonly BombeiroRepository $bombeiros)
    {
    }

    public function executar(string $nomeCompleto, ?string $nomeGuerra, string $tipo): int
    {
        $nomeCompleto = trim($nomeCompleto);
        if ($nomeCompleto === '' || trim($tipo) === '') {
            throw new CampoObrigatorio('Nome completo e Tipo são obrigatórios.');
        }

        return $this->bombeiros->inserir(new Bombeiro(
            id: null,
            nomeCompleto: $nomeCompleto,
            nomeGuerra: Normalizador::opcional($nomeGuerra, Normalizador::TAMANHO_NOME_GUERRA),
            tipo: TipoBombeiro::deCodigo($tipo),
            ativo: true,
            referenciaCiclo: null,
        ));
    }
}
