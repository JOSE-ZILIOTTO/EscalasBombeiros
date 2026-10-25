<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Bombeiro\AnonimizarBombeiros;
use Escala\Application\Bombeiro\CadastrarBombeiro;
use Escala\Application\Bombeiro\ConsultarBombeiros;
use Escala\Application\Bombeiro\InativarBombeiro;
use Escala\Domain\Excecao\ExcecaoDeDominio;
use Escala\Http\Manipulador;
use Escala\Http\Redirecionamento;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaHtml;
use Escala\Http\Seguranca\MensagensFlash;
use Escala\Http\View\RenderizadorDeTemplate;

/** bombeiros.php — lista, cadastro rápido, inativação e anonimização (LGPD). */
final class BombeirosController implements Manipulador
{
    private const PAGINA = 'bombeiros.php';

    public function __construct(
        private readonly ConsultarBombeiros $consultar,
        private readonly CadastrarBombeiro $cadastrar,
        private readonly InativarBombeiro $inativar,
        private readonly AnonimizarBombeiros $anonimizar,
        private readonly int $anosDeRetencao,
        private readonly MensagensFlash $flash,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        if ($requisicao->ehPost()) {
            return $this->processar($requisicao);
        }
        return new RespostaHtml($this->templates->renderizar('bombeiros', [
            'bombeiros' => $this->consultar->todos(),
            'vencidos' => array_map(static fn ($b): ?int => $b->id, $this->anonimizar->vencidos()),
            'anosDeRetencao' => $this->anosDeRetencao,
            'flash' => $this->flash->consumir(),
        ]));
    }

    /** Post/Redirect/Get: o resultado vai como flash e um F5 não reenvia o formulário. */
    private function processar(Requisicao $requisicao): Resposta
    {
        try {
            if ($requisicao->marcado('add_bombeiro')) {
                $this->cadastrar->executar(
                    $requisicao->campo('nome_completo') ?? '',
                    $requisicao->campo('nome_guerra'),
                    $requisicao->campo('tipo') ?? '',
                );
                $this->flash->sucesso('Bombeiro adicionado com sucesso!');
            } elseif ($requisicao->campoInteiro('delete_bombeiro_id') > 0) {
                $this->inativar->executar($requisicao->campoInteiro('delete_bombeiro_id'));
                $this->flash->sucesso('Bombeiro marcado como inativo.');
            } elseif ($requisicao->campoInteiro('anonimizar_bombeiro_id') > 0) {
                $this->anonimizar->executar($requisicao->campoInteiro('anonimizar_bombeiro_id'));
                $this->flash->sucesso('Dados pessoais apagados. O nome continua no histórico de plantões.');
            } elseif ($requisicao->marcado('anonimizar_vencidos')) {
                $total = $this->anonimizar->executarVencidos();
                $this->flash->sucesso("Dados pessoais apagados de {$total} bombeiro(s) com prazo de retenção vencido.");
            }
        } catch (ExcecaoDeDominio $erro) {
            $this->flash->erro($erro->getMessage());
        }
        return new Redirecionamento(self::PAGINA);
    }
}
