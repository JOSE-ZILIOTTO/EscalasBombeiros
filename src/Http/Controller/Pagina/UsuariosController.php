<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Bombeiro\ConsultarBombeiros;
use Escala\Application\Usuario\AlterarSituacaoUsuario;
use Escala\Application\Usuario\ConsultarUsuarios;
use Escala\Application\Usuario\ResultadoSalvarUsuario;
use Escala\Application\Usuario\SalvarUsuario;
use Escala\Application\Usuario\SalvarUsuarioEntrada;
use Escala\Domain\Excecao\ExcecaoDeDominio;
use Escala\Domain\Excecao\UsuarioNaoEncontrado;
use Escala\Domain\Usuario\Usuario;
use Escala\Http\Manipulador;
use Escala\Http\Middleware\ExigirAutenticacao;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\Seguranca\MensagensFlash;
use Escala\Http\View\RenderizadorDeTemplate;

/**
 * gera_hash.php — lista de logins, ativação/desativação e formulário de criar/substituir
 * (nome do arquivo mantido para não quebrar links).
 */
final class UsuariosController implements Manipulador
{
    private const PAGINA = 'gera_hash.php';

    public function __construct(
        private readonly SalvarUsuario $salvar,
        private readonly AlterarSituacaoUsuario $alterarSituacao,
        private readonly ConsultarUsuarios $usuarios,
        private readonly ConsultarBombeiros $bombeiros,
        private readonly MensagensFlash $flash,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        if ($requisicao->ehPost()) {
            return $requisicao->campo('acao') === 'situacao'
                ? $this->alterarSituacao($requisicao)
                : $this->salvar($requisicao);
        }

        $flash = $this->flash->consumir();
        $aviso = $flash === null ? null : ['sucesso' => $flash['tipo'] === 'success', 'mensagem' => $flash['mensagem']];

        return $this->pagina($requisicao, $this->formularioParaEdicao($requisicao), $aviso, abrirFormulario: $requisicao->consulta('novo') !== null);
    }

    private function alterarSituacao(Requisicao $requisicao): Resposta
    {
        $ativar = $requisicao->campo('ativo') === '1';
        try {
            $usuario = $this->alterarSituacao->executar($requisicao->campoInteiro('usuario_id'), $ativar);
            $this->flash->sucesso(sprintf('Usuário "%s" %s.', $usuario->login, $ativar ? 'ativado' : 'desativado'));
        } catch (ExcecaoDeDominio $erro) {
            $this->flash->erro($erro->getMessage());
        }
        return new Redirecionamento(self::PAGINA);
    }

    private function salvar(Requisicao $requisicao): Resposta
    {
        $login = trim($requisicao->campo('usuario') ?? '');
        try {
            $resultado = $this->salvar->executar(new SalvarUsuarioEntrada(
                nome: $requisicao->campo('nome') ?? '',
                login: $login,
                senha: $requisicao->campo('senha') ?? '',
                papel: $requisicao->campo('tipo') ?? 'bc',
                ativo: $requisicao->marcado('ativo'),
                sobrescrever: $requisicao->marcado('force'),
                bombeiroId: $requisicao->campoInteiro('bombeiro_id') ?: null,
            ));
        } catch (ExcecaoDeDominio $erro) {
            // reexibe o que foi digitado (menos a senha) para só corrigir o problema
            $corpo = $requisicao->corpo();
            unset($corpo['senha'], $corpo['csrf_token']);
            return $this->pagina($requisicao, $corpo, ['sucesso' => false, 'mensagem' => $erro->getMessage()], 422, abrirFormulario: true);
        }

        $this->flash->sucesso($resultado === ResultadoSalvarUsuario::Criado
            ? "Usuário \"{$login}\" criado."
            : "Usuário \"{$login}\" atualizado.");
        return new Redirecionamento(self::PAGINA);
    }

    /**
     * "Editar" na lista abre o formulário já preenchido e com "Substituir" marcado;
     * a senha precisa ser digitada de novo porque não é possível recuperá-la do hash.
     *
     * @return array<string, mixed>
     */
    private function formularioParaEdicao(Requisicao $requisicao): array
    {
        $id = $requisicao->consultaInteira('editar');
        if ($id === 0) {
            return ['ativo' => 'on'];
        }
        try {
            $usuario = $this->usuarios->buscar($id);
        } catch (UsuarioNaoEncontrado) {
            return ['ativo' => 'on'];
        }
        return array_filter([
            'nome' => $usuario->nome,
            'usuario' => $usuario->login,
            'tipo' => $usuario->papel->value,
            'bombeiro_id' => $usuario->bombeiroId === null ? null : (string) $usuario->bombeiroId,
            'ativo' => $usuario->ativo ? 'on' : null,
            'force' => 'on',
        ], static fn (?string $valor): bool => $valor !== null);
    }

    /**
     * @param array<string, mixed> $formulario
     * @param array{sucesso: bool, mensagem: string}|null $aviso
     */
    private function pagina(Requisicao $requisicao, array $formulario, ?array $aviso, int $status = 200, bool $abrirFormulario = false): RespostaHtml
    {
        $nomesDosBombeiros = [];
        foreach ($this->bombeiros->todos() as $bombeiro) {
            $nomesDosBombeiros[(int) $bombeiro->id] = $bombeiro->nomeCompleto;
        }
        /** @var Usuario|null $logado */
        $logado = $requisicao->atributo(ExigirAutenticacao::ATRIBUTO_USUARIO);

        $editando = isset($formulario['force']) && $requisicao->consultaInteira('editar') > 0;

        return new RespostaHtml($this->templates->renderizar('usuarios', [
            // erro de validação fica dentro do modal, junto do que foi digitado
            'aviso' => $abrirFormulario && $status !== 200 ? null : $aviso,
            'erroDoFormulario' => $abrirFormulario && $status !== 200 ? $aviso['mensagem'] ?? null : null,
            'form' => $formulario,
            'editando' => $editando,
            'abrirFormulario' => $abrirFormulario || $editando,
            'usuarios' => $this->usuarios->todos(),
            'adminsAtivos' => $this->usuarios->adminsAtivos(),
            'idLogado' => $logado?->id,
            'nomesDosBombeiros' => $nomesDosBombeiros,
            'bombeirosAtivos' => $this->bombeiros->ativos(),
        ]), $status);
    }
}
