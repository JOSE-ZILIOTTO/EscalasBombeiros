<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

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

/** bombeiros.php — lista, cadastro rápido e inativação. */
final class BombeirosController implements Manipulador
{
    private const PAGINA = 'bombeiros.php';

    public function __construct(
        private readonly ConsultarBombeiros $consultar,
        private readonly CadastrarBombeiro $cadastrar,
        private readonly InativarBombeiro $inativar,
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
            }
        } catch (ExcecaoDeDominio $erro) {
            $this->flash->erro($erro->getMessage());
        }
        return new Redirecionamento(self::PAGINA);
    }
}
