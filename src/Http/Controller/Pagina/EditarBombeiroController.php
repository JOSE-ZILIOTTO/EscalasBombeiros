<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Bombeiro\AtualizarBombeiro;
use Escala\Application\Bombeiro\AtualizarBombeiroEntrada;
use Escala\Application\Bombeiro\ConsultarBombeiros;
use Escala\Domain\Bombeiro\Bombeiro;
use Escala\Domain\Excecao\BombeiroNaoEncontrado;
use Escala\Domain\Excecao\ExcecaoDeDominio;
use Escala\Http\Manipulador;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\Seguranca\MensagensFlash;
use Escala\Http\View\RenderizadorDeTemplate;

/** editar_bombeiro.php?id= */
final class EditarBombeiroController implements Manipulador
{
    private const LISTA = 'bombeiros.php';

    /** Campos que não vão para o HTML: a tela mostra a máscara e busca o valor só sob demanda. */
    public const CAMPOS_SIGILOSOS = ['cpf', 'dados_bancarios'];

    /** Campos de texto do formulário, com o mesmo nome das colunas do banco. */
    private const CAMPOS_TEXTO = [
        'nome_completo', 'nome_guerra', 'cpf', 'tipo', 'fixo_ref_data', 'fixo_ref_dia_ciclo',
        'endereco_rua', 'endereco_numero', 'endereco_bairro', 'endereco_cidade', 'endereco_uf', 'endereco_cep',
        'telefone_principal', 'contato_emergencia_nome', 'contato_emergencia_fone', 'dados_bancarios',
        'tamanho_gandola', 'tamanho_camiseta', 'tamanho_calca', 'tamanho_calcado',
    ];

    public function __construct(
        private readonly ConsultarBombeiros $consultar,
        private readonly AtualizarBombeiro $atualizar,
        private readonly MensagensFlash $flash,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        $id = $requisicao->consultaInteira('id');
        if ($id === 0) {
            $this->flash->erro('ID de bombeiro inválido.');
            return new Redirecionamento(self::LISTA);
        }
        return $requisicao->ehPost() ? $this->salvar($id, $requisicao) : $this->exibir($id);
    }

    private function exibir(int $id): Resposta
    {
        try {
            $bombeiro = $this->consultar->buscar($id);
        } catch (BombeiroNaoEncontrado $erro) {
            $this->flash->erro($erro->getMessage());
            return new Redirecionamento(self::LISTA);
        }
        return $this->formulario($bombeiro, $this->valoresDe($bombeiro), $this->flash->consumir());
    }

    private function salvar(int $id, Requisicao $requisicao): Resposta
    {
        if ($requisicao->campoInteiro('id') !== $id) {
            $this->flash->erro('Erro: ID no formulário não confere.');
            return new Redirecionamento(self::LISTA);
        }
        $campo = static fn (string $nome): ?string => $requisicao->campo($nome);

        try {
            $this->atualizar->executar(new AtualizarBombeiroEntrada(
                id: $id,
                nomeCompleto: $campo('nome_completo') ?? '',
                tipo: $campo('tipo') ?? '',
                ativo: $requisicao->marcado('ativo'),
                nomeGuerra: $campo('nome_guerra'),
                cpf: $campo('cpf'),
                fixoRefData: $campo('fixo_ref_data'),
                fixoRefDiaCiclo: $campo('fixo_ref_dia_ciclo'),
                enderecoRua: $campo('endereco_rua'),
                enderecoNumero: $campo('endereco_numero'),
                enderecoBairro: $campo('endereco_bairro'),
                enderecoCidade: $campo('endereco_cidade'),
                enderecoUf: $campo('endereco_uf'),
                enderecoCep: $campo('endereco_cep'),
                telefonePrincipal: $campo('telefone_principal'),
                contatoEmergenciaNome: $campo('contato_emergencia_nome'),
                contatoEmergenciaFone: $campo('contato_emergencia_fone'),
                dadosBancarios: $campo('dados_bancarios'),
                tamanhoGandola: $campo('tamanho_gandola'),
                tamanhoCamiseta: $campo('tamanho_camiseta'),
                tamanhoCalca: $campo('tamanho_calca'),
                tamanhoCalcado: $campo('tamanho_calcado'),
            ));
        } catch (ExcecaoDeDominio $erro) {
            // reexibe o que foi digitado para o usuário só corrigir o campo com problema;
            // um campo sigiloso que não foi aberto para edição continua fechado (null)
            $valores = array_map(static fn (string $nome): ?string => $campo($nome), array_combine(self::CAMPOS_TEXTO, self::CAMPOS_TEXTO));
            $valores = array_map(static fn (?string $v): ?string => $v ?? '', array_diff_key($valores, array_flip(self::CAMPOS_SIGILOSOS)))
                + array_intersect_key($valores, array_flip(self::CAMPOS_SIGILOSOS));
            $valores['ativo'] = $requisicao->marcado('ativo');
            return $this->formulario($this->consultar->buscar($id), $valores, ['tipo' => 'error', 'mensagem' => $erro->getMessage()], 422);
        }

        $this->flash->sucesso('Bombeiro atualizado com sucesso!');
        return new Redirecionamento(self::LISTA);
    }

    /**
     * @param array<string, string|bool|null> $valores
     * @param array{tipo: string, mensagem: string}|null $flash
     */
    private function formulario(Bombeiro $bombeiro, array $valores, ?array $flash, int $status = 200): RespostaHtml
    {
        $d = $bombeiro->dados;
        return new RespostaHtml(
            $this->templates->renderizar('editar_bombeiro', [
                'id' => (int) $bombeiro->id,
                'b' => $valores,
                'mascaras' => [
                    'cpf' => $d->cpf?->mascarado(),
                    'dados_bancarios' => $d->dadosBancarios === null ? null : '•••••• ' . mb_substr($d->dadosBancarios, -4),
                ],
                'anonimizado' => $bombeiro->anonimizado,
                'flash' => $flash,
            ]),
            $status,
        );
    }

    /** @return array<string, string|bool|null> CPF e dados bancários ficam de fora (null = campo fechado) */
    private function valoresDe(Bombeiro $bombeiro): array
    {
        $d = $bombeiro->dados;
        return [
            'nome_completo' => $bombeiro->nomeCompleto,
            'nome_guerra' => (string) $bombeiro->nomeGuerra,
            'cpf' => null,
            'tipo' => $bombeiro->tipo->value,
            'ativo' => $bombeiro->ativo,
            'fixo_ref_data' => (string) $bombeiro->referenciaCiclo?->data->emTexto(),
            'fixo_ref_dia_ciclo' => (string) $bombeiro->referenciaCiclo?->diaDoCiclo,
            'endereco_rua' => (string) $d->enderecoRua,
            'endereco_numero' => (string) $d->enderecoNumero,
            'endereco_bairro' => (string) $d->enderecoBairro,
            'endereco_cidade' => (string) $d->enderecoCidade,
            'endereco_uf' => (string) $d->enderecoUf,
            'endereco_cep' => (string) $d->enderecoCep,
            'telefone_principal' => (string) $d->telefonePrincipal,
            'contato_emergencia_nome' => (string) $d->contatoEmergenciaNome,
            'contato_emergencia_fone' => (string) $d->contatoEmergenciaFone,
            'dados_bancarios' => null,
            'tamanho_gandola' => (string) $d->tamanhoGandola,
            'tamanho_camiseta' => (string) $d->tamanhoCamiseta,
            'tamanho_calca' => (string) $d->tamanhoCalca,
            'tamanho_calcado' => (string) $d->tamanhoCalcado,
        ];
    }
}
