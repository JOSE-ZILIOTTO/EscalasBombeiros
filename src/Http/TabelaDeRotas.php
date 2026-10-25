<?php

declare(strict_types=1);

namespace Escala\Http;

use Escala\Http\Controller\Api;
use Escala\Http\Controller\Pagina;
use InvalidArgumentException;

/**
 * Cada arquivo de entrada (escala.php, api/*.php…) chama uma rota por nome. As URLs antigas
 * continuam iguais, então o JavaScript e os links existentes não mudam.
 */
final class TabelaDeRotas
{
    public function rota(string $nome): Rota
    {
        return $this->definicoes()[$nome] ?? throw new InvalidArgumentException("Rota desconhecida: {$nome}");
    }

    /** @return array<string, Rota> */
    private function definicoes(): array
    {
        $json = FormatoResposta::Json;
        $html = FormatoResposta::Html;
        $post = ['POST'];
        $getPost = ['GET', 'POST'];

        return [
            // páginas
            'login' => new Rota(Pagina\LoginController::class, $html, Acesso::Publico, $getPost, verificaCsrf: false),
            'logout' => new Rota(Pagina\LogoutController::class, $html, Acesso::Publico),
            'escala' => new Rota(Pagina\EscalaController::class, $html, Acesso::Admin),
            'bombeiros' => new Rota(Pagina\BombeirosController::class, $html, Acesso::Admin, $getPost),
            'editar_bombeiro' => new Rota(Pagina\EditarBombeiroController::class, $html, Acesso::Admin, $getPost),
            'usuarios' => new Rota(Pagina\UsuariosController::class, $html, Acesso::Admin, $getPost),
            'exportacao' => new Rota(Pagina\ExportacaoController::class, $html, Acesso::Admin, $getPost),
            'exportacao_excel' => new Rota(Pagina\ExportacaoExcelController::class, $html, Acesso::Admin),
            'portal' => new Rota(Pagina\PortalController::class, $html, Acesso::Logado),
            'meus_dados' => new Rota(Pagina\MeusDadosController::class, $html, Acesso::Logado),
            'privacidade' => new Rota(Pagina\PrivacidadeController::class, $html, Acesso::Publico),

            // API (JSON)
            'api.consultar_dia' => new Rota(Api\ConsultarDiaController::class, $json, Acesso::Admin),
            'api.registrar_plantao' => new Rota(Api\RegistrarPlantaoController::class, $json, Acesso::Admin, $post),
            'api.remover_plantao' => new Rota(Api\RemoverPlantaoController::class, $json, Acesso::Admin, $post),
            'api.registrar_excecao_fixo' => new Rota(Api\RegistrarExcecaoFixoController::class, $json, Acesso::Admin, $post),
            'api.remover_excecao_fixo' => new Rota(Api\RemoverExcecaoFixoController::class, $json, Acesso::Admin, $post),
            'api.avancar_ordem' => new Rota(Api\AvancarOrdemController::class, $json, Acesso::Admin, $post),
            'api.concluir_tutorial' => new Rota(Api\ConcluirTutorialController::class, $json, Acesso::Admin, $post),
            'api.definir_inicio_ordem' => new Rota(Api\DefinirInicioOrdemController::class, $json, Acesso::Admin, $post),
            'api.revelar_dado' => new Rota(Api\RevelarDadoPessoalController::class, $json, Acesso::Admin, $post),
        ];
    }
}
