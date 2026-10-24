<?php

declare(strict_types=1);

namespace Escala\Application\Bombeiro;

use Escala\Application\OrdemEscolha\CarregadorDaOrdem;
use Escala\Application\Transacao;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\Cpf;
use Escala\Domain\Bombeiro\DadosCadastrais;
use Escala\Domain\Bombeiro\TipoBombeiro;
use Escala\Domain\Escala\DataEscala;
use Escala\Domain\Escala\ReferenciaCicloFixo;
use Escala\Domain\Excecao\BombeiroNaoEncontrado;
use Escala\Domain\Excecao\CicloFixoInvalido;
use Escala\Domain\Excecao\DataInvalida;

final class AtualizarBombeiro
{
    public function __construct(
        private readonly Transacao $transacao,
        private readonly BombeiroRepository $bombeiros,
        private readonly CarregadorDaOrdem $ordem,
    ) {
    }

    public function executar(AtualizarBombeiroEntrada $entrada): void
    {
        $this->transacao->executar(function () use ($entrada): void {
            $atual = $this->bombeiros->buscar($entrada->id) ?? throw new BombeiroNaoEncontrado();
            $tipo = TipoBombeiro::deCodigo($entrada->tipo);

            $atualizado = new Bombeiro(
                id: $atual->id,
                nomeCompleto: trim($entrada->nomeCompleto),
                nomeGuerra: Normalizador::opcional($entrada->nomeGuerra, Normalizador::TAMANHO_NOME_GUERRA),
                tipo: $tipo,
                ativo: $entrada->ativo,
                referenciaCiclo: $tipo === TipoBombeiro::Fixo ? $this->referenciaCiclo($entrada) : null,
                dados: $this->dadosCadastrais($entrada, $atual),
            );
            $atualizado->validarParaGravacao();
            $this->bombeiros->atualizar($atualizado);

            if (!$atualizado->ativo) {
                $carregada = $this->ordem->carregar();
                $this->ordem->salvar($carregada->ordem->semBombeiroNaVez($entrada->id));
            }
        });
    }

    private function referenciaCiclo(AtualizarBombeiroEntrada $entrada): ReferenciaCicloFixo
    {
        $dia = filter_var($entrada->fixoRefDiaCiclo, FILTER_VALIDATE_INT);
        if ($dia === false || Normalizador::opcional($entrada->fixoRefData) === null) {
            throw new CicloFixoInvalido();
        }
        try {
            return new ReferenciaCicloFixo(DataEscala::deTexto((string) $entrada->fixoRefData), $dia);
        } catch (DataInvalida) {
            throw new CicloFixoInvalido();
        }
    }

    private function dadosCadastrais(AtualizarBombeiroEntrada $entrada, Bombeiro $atual): DadosCadastrais
    {
        return new DadosCadastrais(
            cpf: $this->cpf($entrada->cpf, $atual->dados->cpf),
            enderecoRua: Normalizador::opcional($entrada->enderecoRua),
            enderecoNumero: Normalizador::opcional($entrada->enderecoNumero),
            enderecoBairro: Normalizador::opcional($entrada->enderecoBairro),
            enderecoCidade: Normalizador::opcional($entrada->enderecoCidade),
            enderecoUf: Normalizador::opcional($entrada->enderecoUf, 2),
            enderecoCep: Normalizador::opcional($entrada->enderecoCep),
            telefonePrincipal: Normalizador::opcional($entrada->telefonePrincipal),
            contatoEmergenciaNome: Normalizador::opcional($entrada->contatoEmergenciaNome),
            contatoEmergenciaFone: Normalizador::opcional($entrada->contatoEmergenciaFone),
            dadosBancarios: Normalizador::opcional($entrada->dadosBancarios),
            tamanhoGandola: Normalizador::opcional($entrada->tamanhoGandola),
            tamanhoCamiseta: Normalizador::opcional($entrada->tamanhoCamiseta),
            tamanhoCalca: Normalizador::opcional($entrada->tamanhoCalca),
            tamanhoCalcado: Normalizador::opcional($entrada->tamanhoCalcado),
        );
    }

    /** CPF só é validado quando muda; o já gravado é mantido mesmo fora do padrão. */
    private function cpf(?string $informado, ?Cpf $atual): ?Cpf
    {
        $informado = Normalizador::opcional($informado);
        if ($informado === null) {
            return null;
        }
        if ($atual !== null && $atual->igual($informado)) {
            return $atual;
        }
        return Cpf::criar($informado);
    }
}
