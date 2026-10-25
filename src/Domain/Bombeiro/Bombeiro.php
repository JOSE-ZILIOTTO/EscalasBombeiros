<?php

declare(strict_types=1);

namespace Escala\Domain\Bombeiro;

use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ReferenciaCicloFixo;
use Escala\Domain\Excecao\AnonimizacaoNaoPermitida;
use Escala\Domain\Excecao\CampoObrigatorio;
use Escala\Domain\Excecao\CicloFixoInvalido;

final readonly class Bombeiro
{
    public function __construct(
        public ?int $id,
        public string $nomeCompleto,
        public ?string $nomeGuerra,
        public TipoBombeiro $tipo,
        public bool $ativo,
        public ?ReferenciaCicloFixo $referenciaCiclo,
        public DadosCadastrais $dados = new DadosCadastrais(),
        /** Preenchido pelo banco ao inativar; conta o prazo de retenção dos dados pessoais. */
        public ?DataEscala $inativadoEm = null,
        /** Dados pessoais já apagados; o nome fica para o histórico de plantões. */
        public bool $anonimizado = false,
    ) {
    }

    /**
     * Regras para gravar um cadastro novo ou alterado. Fica fora do construtor de
     * propósito: registros antigos incompletos ainda precisam ser carregados do banco.
     */
    public function validarParaGravacao(): void
    {
        if (trim($this->nomeCompleto) === '') {
            throw new CampoObrigatorio('Nome completo e Tipo são obrigatórios.');
        }
        if ($this->tipo === TipoBombeiro::Fixo && $this->referenciaCiclo === null) {
            throw new CicloFixoInvalido();
        }
    }

    public function ehFixo(): bool
    {
        return $this->tipo === TipoBombeiro::Fixo;
    }

    public function inativado(): self
    {
        return $this->com(ativo: false);
    }

    /**
     * Apaga os dados pessoais e mantém nome, nome de guerra e tipo, que aparecem nas
     * planilhas já emitidas. Só para quem saiu: um ativo ainda precisa do cadastro.
     *
     * @throws AnonimizacaoNaoPermitida
     */
    public function anonimizado(): self
    {
        if ($this->ativo) {
            throw new AnonimizacaoNaoPermitida();
        }
        return $this->com(dados: new DadosCadastrais(), anonimizado: true);
    }

    public function temDadosPessoais(): bool
    {
        return !$this->dados->vazio();
    }

    private function com(?bool $ativo = null, ?DadosCadastrais $dados = null, ?bool $anonimizado = null): self
    {
        return new self(
            $this->id,
            $this->nomeCompleto,
            $this->nomeGuerra,
            $this->tipo,
            $ativo ?? $this->ativo,
            $this->referenciaCiclo,
            $dados ?? $this->dados,
            $this->inativadoEm,
            $anonimizado ?? $this->anonimizado,
        );
    }
}
