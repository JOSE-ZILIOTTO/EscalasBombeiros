<?php

declare(strict_types=1);

namespace Escala\Http\Controller\Pagina;

use Escala\Application\Portal\ConsultarMeusDados;
use Escala\Application\Relogio;
use Escala\Domain\Usuario\Usuario;
use Escala\Http\Manipulador;
use Escala\Http\Middleware\ExigirAutenticacao;
use Escala\Http\Requisicao;
use Escala\Http\Resposta;
use Escala\Http\RespostaArquivo;
use Escala\Http\RespostaHtml;
use Escala\Http\View\RenderizadorDeTemplate;

/** meus_dados.php — o que o sistema guarda sobre quem está logado; ?formato=json baixa uma cópia. */
final class MeusDadosController implements Manipulador
{
    public function __construct(
        private readonly ConsultarMeusDados $consultar,
        private readonly Relogio $relogio,
        private readonly RenderizadorDeTemplate $templates,
    ) {
    }

    public function tratar(Requisicao $requisicao): Resposta
    {
        /** @var Usuario $usuario */
        $usuario = $requisicao->atributo(ExigirAutenticacao::ATRIBUTO_USUARIO);
        $meusDados = $this->consultar->executar($usuario);

        if ($requisicao->consulta('formato') === 'json') {
            $agora = $this->relogio->agora();
            return new RespostaArquivo(
                json_encode(
                    $meusDados->exportacao($agora->format(DATE_ATOM)),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
                'meus-dados-' . $agora->format('Y-m-d') . '.json',
                'application/json; charset=utf-8',
            );
        }

        return new RespostaHtml($this->templates->renderizar('meus_dados', ['dados' => $meusDados]));
    }
}
