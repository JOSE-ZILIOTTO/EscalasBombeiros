<?php

declare(strict_types=1);

namespace Escala\Infrastructure;

use Closure;
use DateTimeZone;
use Escala\Application\Auth\Autenticador;
use Escala\Application\Auth\Sessao;
use Escala\Application\Bombeiro\AnonimizarBombeiros;
use Escala\Application\Bombeiro\AtualizarBombeiro;
use Escala\Application\Bombeiro\CadastrarBombeiro;
use Escala\Application\Bombeiro\ConsultarBombeiros;
use Escala\Application\Bombeiro\InativarBombeiro;
use Escala\Application\Escala\ConsultarDia;
use Escala\Application\Escala\MontarCalendarioMes;
use Escala\Application\Escala\RegistrarExcecaoFixo;
use Escala\Application\Escala\RegistrarPlantao;
use Escala\Application\Escala\RemoverExcecaoFixo;
use Escala\Application\Escala\RemoverPlantao;
use Escala\Application\Exportacao\FormatadorDeNomeDestaque;
use Escala\Application\Exportacao\GerarPlanilhaMensal;
use Escala\Application\Exportacao\SalvarAssinatura;
use Escala\Application\OrdemEscolha\AvancarOrdem;
use Escala\Application\OrdemEscolha\CarregadorDaOrdem;
use Escala\Application\OrdemEscolha\ConsultarOrdemDeEscolha;
use Escala\Application\OrdemEscolha\DefinirInicioOrdem;
use Escala\Application\Portal\ConsultarMeuPortal;
use Escala\Application\Portal\ConsultarMeusDados;
use Escala\Application\Relogio;
use Escala\Application\Transacao;
use Escala\Application\Usuario\AlterarSituacaoUsuario;
use Escala\Application\Usuario\ConsultarUsuarios;
use Escala\Application\Usuario\SalvarUsuario;
use Escala\Domain\Bombeiro\BombeiroRepository;
use Escala\Domain\Bombeiro\PoliticaDeRetencao;
use Escala\Domain\Escala\CicloFixo;
use Escala\Domain\Escala\EscalaDoFixo;
use Escala\Domain\Escala\ExcecaoFixoRepository;
use Escala\Domain\Escala\MontadorDeQuadro;
use Escala\Domain\Escala\PlantaoRepository;
use Escala\Domain\Escala\RegraDeVagas;
use Escala\Domain\Escala\RegraDoPlantonista;
use Escala\Domain\Escala\TabelaDeRemuneracao;
use Escala\Domain\OrdemEscolha\OrdemEscolhaRepository;
use Escala\Domain\Planilha\AssinaturaRepository;
use Escala\Domain\Shared\Dinheiro;
use Escala\Domain\Usuario\ProtecaoUltimoAdministrador;
use Escala\Domain\Usuario\TutorialRepository;
use Escala\Domain\Usuario\UsuarioRepository;
use Escala\Http\Controller\Api;
use Escala\Http\Controller\Pagina;
use Escala\Http\Manipulador;
use Escala\Http\Seguranca\MensagensFlash;
use Escala\Http\Seguranca\TokensCsrf;
use Escala\Http\View\AjudanteDeView;
use Escala\Http\View\RenderizadorDeTemplate;
use Escala\Http\View\RenderizadorPhp;
use Escala\Infrastructure\Config\Config;
use Escala\Infrastructure\Log\ErrorLogLogger;
use Escala\Infrastructure\Exportacao\PlanilhaXlsx;
use Escala\Infrastructure\Persistencia\FabricaDeConexao;
use Escala\Infrastructure\Persistencia\PdoAssinaturaRepository;
use Escala\Infrastructure\Persistencia\PdoBombeiroRepository;
use Escala\Infrastructure\Persistencia\PdoExcecaoFixoRepository;
use Escala\Infrastructure\Persistencia\PdoOrdemEscolhaRepository;
use Escala\Infrastructure\Persistencia\PdoPlantaoRepository;
use Escala\Infrastructure\Persistencia\PdoTransacao;
use Escala\Infrastructure\Persistencia\PdoTutorialRepository;
use Escala\Infrastructure\Persistencia\PdoUsuarioRepository;
use Escala\Infrastructure\Seguranca\CifraDeDados;
use Escala\Infrastructure\Seguranca\LimitadorEmArquivo;
use Escala\Infrastructure\Seguranca\SessaoNativa;
use InvalidArgumentException;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Raiz de composição (DI manual). Getters tipados em vez de um container genérico
 * por string: um erro de ligação aparece na análise estática, não em produção.
 * Cada instância é criada sob demanda e reaproveitada só dentro desta requisição.
 */
final class ContainerDaAplicacao
{
    /** @var array<string, object> */
    private array $instancias = [];

    public function __construct(
        private readonly Config $config,
        private readonly string $diretorioDeTemplates,
        private readonly bool $conexaoSegura,
    ) {
    }

    /** @param class-string<Manipulador> $classe */
    public function controlador(string $classe): Manipulador
    {
        return match ($classe) {
            Pagina\LoginController::class => new Pagina\LoginController($this->autenticador(), $this->csrf(), $this->templates()),
            Pagina\LogoutController::class => new Pagina\LogoutController($this->autenticador()),
            Pagina\EscalaController::class => new Pagina\EscalaController(
                new MontarCalendarioMes($this->bombeiros(), $this->plantoes(), $this->excecoes(), $this->montadorDeQuadro(), $this->consultarOrdem()),
                $this->tutorial(),
                $this->relogio(),
                $this->templates(),
            ),
            Pagina\BombeirosController::class => new Pagina\BombeirosController(
                $this->consultarBombeiros(),
                new CadastrarBombeiro($this->bombeiros()),
                new InativarBombeiro($this->transacao(), $this->bombeiros(), $this->carregadorDaOrdem()),
                new AnonimizarBombeiros($this->transacao(), $this->bombeiros(), $this->retencao(), $this->relogio()),
                $this->retencao()->anos,
                $this->flash(),
                $this->templates(),
            ),
            Pagina\EditarBombeiroController::class => new Pagina\EditarBombeiroController(
                $this->consultarBombeiros(),
                new AtualizarBombeiro($this->transacao(), $this->bombeiros(), $this->carregadorDaOrdem()),
                $this->flash(),
                $this->templates(),
            ),
            Pagina\UsuariosController::class => new Pagina\UsuariosController(
                new SalvarUsuario($this->transacao(), $this->usuarios(), new ProtecaoUltimoAdministrador()),
                new AlterarSituacaoUsuario($this->transacao(), $this->usuarios(), new ProtecaoUltimoAdministrador()),
                new ConsultarUsuarios($this->usuarios()),
                $this->consultarBombeiros(),
                $this->flash(),
                $this->templates(),
            ),
            Pagina\ExportacaoController::class => new Pagina\ExportacaoController(
                new GerarPlanilhaMensal($this->bombeiros(), $this->plantoes(), $this->excecoes(), $this->cicloFixo()),
                new FormatadorDeNomeDestaque(),
                $this->assinaturas(),
                new SalvarAssinatura($this->transacao(), $this->assinaturas()),
                $this->flash(),
                $this->relogio(),
                $this->templates(),
            ),
            Pagina\ExportacaoExcelController::class => new Pagina\ExportacaoExcelController(
                new GerarPlanilhaMensal($this->bombeiros(), $this->plantoes(), $this->excecoes(), $this->cicloFixo()),
                $this->assinaturas(),
                new PlanilhaXlsx(new FormatadorDeNomeDestaque()),
                $this->relogio(),
            ),
            Pagina\PortalController::class => new Pagina\PortalController(
                new ConsultarMeuPortal(
                    $this->usuarios(),
                    $this->bombeiros(),
                    $this->plantoes(),
                    $this->excecoes(),
                    $this->cicloFixo(),
                    $this->tabelaDeRemuneracao(),
                ),
                $this->relogio(),
                $this->templates(),
            ),
            Pagina\MeusDadosController::class => new Pagina\MeusDadosController(
                new ConsultarMeusDados($this->bombeiros(), $this->plantoes()),
                $this->relogio(),
                $this->templates(),
            ),
            Pagina\PrivacidadeController::class => new Pagina\PrivacidadeController(
                $this->templates(),
                $this->config->texto('PRIVACIDADE_CONTROLADOR') ?: 'Coordenação dos bombeiros civis (BC) da unidade',
                $this->config->texto('PRIVACIDADE_CONTATO') ?: null,
                $this->retencao()->anos,
            ),

            Api\ConsultarDiaController::class => new Api\ConsultarDiaController(
                new ConsultarDia($this->bombeiros(), $this->plantoes(), $this->excecoes(), $this->montadorDeQuadro(), $this->consultarOrdem()),
            ),
            Api\RegistrarPlantaoController::class => new Api\RegistrarPlantaoController(new RegistrarPlantao(
                $this->transacao(),
                $this->bombeiros(),
                $this->plantoes(),
                $this->excecoes(),
                $this->montadorDeQuadro(),
                $this->regraDeVagas(),
                new RegraDoPlantonista($this->cicloFixo()),
            )),
            Api\RemoverPlantaoController::class => new Api\RemoverPlantaoController(new RemoverPlantao($this->plantoes())),
            Api\RegistrarExcecaoFixoController::class => new Api\RegistrarExcecaoFixoController(
                new RegistrarExcecaoFixo($this->bombeiros(), $this->excecoes(), $this->cicloFixo()),
            ),
            Api\RemoverExcecaoFixoController::class => new Api\RemoverExcecaoFixoController(new RemoverExcecaoFixo($this->excecoes())),
            Api\AvancarOrdemController::class => new Api\AvancarOrdemController(new AvancarOrdem($this->transacao(), $this->carregadorDaOrdem())),
            Api\DefinirInicioOrdemController::class => new Api\DefinirInicioOrdemController(
                new DefinirInicioOrdem($this->transacao(), $this->carregadorDaOrdem()),
            ),
            Api\ConcluirTutorialController::class => new Api\ConcluirTutorialController($this->tutorial()),
            Api\RevelarDadoPessoalController::class => new Api\RevelarDadoPessoalController($this->consultarBombeiros()),
            default => throw new InvalidArgumentException("Controller não registrado: {$classe}"),
        };
    }

    // --- Infraestrutura ---

    public function conexaoSegura(): bool
    {
        return $this->conexaoSegura;
    }

    /** FORCAR_HTTPS=1 em produção: http:// vira https:// e o navegador passa a exigir HTTPS (HSTS). */
    public function forcarHttps(): bool
    {
        return $this->config->inteiro('FORCAR_HTTPS', 0) === 1;
    }

    public function logger(): LoggerInterface
    {
        return $this->unico(LoggerInterface::class, static fn () => new ErrorLogLogger());
    }

    public function relogio(): Relogio
    {
        return $this->unico(Relogio::class, fn () => new RelogioDoSistema(
            new DateTimeZone($this->config->texto('APP_TIMEZONE', 'America/Sao_Paulo') ?? 'America/Sao_Paulo'),
        ));
    }

    public function pdo(): PDO
    {
        return $this->unico(PDO::class, fn () => (new FabricaDeConexao())->criar($this->config));
    }

    public function transacao(): Transacao
    {
        return $this->unico(Transacao::class, fn () => new PdoTransacao($this->pdo()));
    }

    public function sessao(): Sessao
    {
        return $this->unico(Sessao::class, fn () => new SessaoNativa($this->conexaoSegura));
    }

    public function csrf(): TokensCsrf
    {
        return $this->unico(TokensCsrf::class, fn () => new TokensCsrf($this->sessao()));
    }

    public function flash(): MensagensFlash
    {
        return $this->unico(MensagensFlash::class, fn () => new MensagensFlash($this->sessao()));
    }

    public function templates(): RenderizadorDeTemplate
    {
        return $this->unico(RenderizadorDeTemplate::class, fn () => new RenderizadorPhp(
            $this->diretorioDeTemplates,
            new AjudanteDeView($this->csrf(), dirname($this->diretorioDeTemplates)),
        ));
    }

    public function autenticador(): Autenticador
    {
        return $this->unico(Autenticador::class, fn () => new Autenticador(
            $this->usuarios(),
            $this->sessao(),
            new LimitadorEmArquivo(
                sys_get_temp_dir(),
                $this->relogio(),
                $this->config->inteiro('LOGIN_MAX_TENTATIVAS', 5),
                $this->config->inteiro('LOGIN_JANELA_SEGUNDOS', 900),
            ),
        ));
    }

    // --- Repositórios ---

    public function assinaturas(): AssinaturaRepository
    {
        return $this->unico(AssinaturaRepository::class, fn () => new PdoAssinaturaRepository($this->pdo()));
    }

    public function tutorial(): TutorialRepository
    {
        return $this->unico(TutorialRepository::class, fn () => new PdoTutorialRepository($this->pdo(), $this->relogio()));
    }

    public function bombeiros(): BombeiroRepository
    {
        return $this->unico(BombeiroRepository::class, fn () => new PdoBombeiroRepository($this->pdo(), $this->cifra()));
    }

    /** Exige CHAVE_DADOS: sem ela, CPF e dados bancários não podem ser lidos nem gravados. */
    public function cifra(): CifraDeDados
    {
        return $this->unico(CifraDeDados::class, fn () => new CifraDeDados($this->config->textoObrigatorio('CHAVE_DADOS')));
    }

    public function plantoes(): PlantaoRepository
    {
        return $this->unico(PlantaoRepository::class, fn () => new PdoPlantaoRepository($this->pdo()));
    }

    public function excecoes(): ExcecaoFixoRepository
    {
        return $this->unico(ExcecaoFixoRepository::class, fn () => new PdoExcecaoFixoRepository($this->pdo()));
    }

    public function ordens(): OrdemEscolhaRepository
    {
        return $this->unico(OrdemEscolhaRepository::class, fn () => new PdoOrdemEscolhaRepository($this->pdo()));
    }

    public function usuarios(): UsuarioRepository
    {
        return $this->unico(UsuarioRepository::class, fn () => new PdoUsuarioRepository($this->pdo()));
    }

    // --- Domínio e aplicação compartilhados ---

    private function retencao(): PoliticaDeRetencao
    {
        return $this->unico(PoliticaDeRetencao::class, fn () => new PoliticaDeRetencao(
            max(1, $this->config->inteiro('RETENCAO_DADOS_INATIVOS_ANOS', 5)),
        ));
    }

    private function cicloFixo(): CicloFixo
    {
        return $this->unico(CicloFixo::class, static fn () => new CicloFixo());
    }

    private function regraDeVagas(): RegraDeVagas
    {
        return $this->unico(RegraDeVagas::class, fn () => new RegraDeVagas($this->config->inteiro('VAGAS_BC_POR_TURNO', 1)));
    }

    private function montadorDeQuadro(): MontadorDeQuadro
    {
        return $this->unico(MontadorDeQuadro::class, fn () => new MontadorDeQuadro(
            new EscalaDoFixo($this->cicloFixo()),
            $this->regraDeVagas(),
        ));
    }

    private function tabelaDeRemuneracao(): TabelaDeRemuneracao
    {
        return $this->unico(TabelaDeRemuneracao::class, fn () => new TabelaDeRemuneracao(
            Dinheiro::reais($this->config->inteiro('REMUNERACAO_TURNO_12H', 125)),
            Dinheiro::reais($this->config->inteiro('REMUNERACAO_TURNO_24H', 250)),
        ));
    }

    private function carregadorDaOrdem(): CarregadorDaOrdem
    {
        return $this->unico(CarregadorDaOrdem::class, fn () => new CarregadorDaOrdem($this->bombeiros(), $this->ordens()));
    }

    private function consultarOrdem(): ConsultarOrdemDeEscolha
    {
        return new ConsultarOrdemDeEscolha($this->carregadorDaOrdem());
    }

    private function consultarBombeiros(): ConsultarBombeiros
    {
        return new ConsultarBombeiros($this->bombeiros());
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @param Closure(): T $fabrica
     * @return T
     */
    private function unico(string $id, Closure $fabrica): object
    {
        return $this->instancias[$id] ??= $fabrica();
    }
}
