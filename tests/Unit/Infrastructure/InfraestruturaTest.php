<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Infrastructure;

use DateTimeImmutable;
use Escala\Application\Relogio;
use Escala\Infrastructure\Config\CarregadorDeEnv;
use Escala\Infrastructure\Config\ConfiguracaoAusente;
use Escala\Infrastructure\Seguranca\LimitadorEmArquivo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InfraestruturaTest extends TestCase
{
    private string $diretorio;

    protected function setUp(): void
    {
        $this->diretorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'escala_teste_' . bin2hex(random_bytes(4));
        mkdir($this->diretorio);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->diretorio . DIRECTORY_SEPARATOR . '*') ?: []);
        rmdir($this->diretorio);
    }

    #[Test]
    public function envAceitaComentariosComCerquilhaESenhasComCaracteresEspeciais(): void
    {
        $arquivo = $this->diretorio . '/config.env';
        file_put_contents($arquivo, "# comentário (com parênteses)\nDB_USERNAME=app\nDB_PASSWORD=\"S3nh@!; =forte\"\n");

        $config = (new CarregadorDeEnv())->carregar($arquivo, []);

        self::assertSame('app', $config->texto('DB_USERNAME'));
        self::assertSame('S3nh@!; =forte', $config->texto('DB_PASSWORD'));
    }

    #[Test]
    public function variavelDeAmbienteRealTemPrioridadeSobreOArquivo(): void
    {
        $arquivo = $this->diretorio . '/config.env';
        file_put_contents($arquivo, "DB_NAME=do_arquivo\n");

        $config = (new CarregadorDeEnv())->carregar($arquivo, ['DB_NAME' => 'do_ambiente']);

        self::assertSame('do_ambiente', $config->texto('DB_NAME'));
    }

    #[Test]
    public function configuracaoObrigatoriaAusenteFalhaComMensagemClara(): void
    {
        $this->expectException(ConfiguracaoAusente::class);

        (new CarregadorDeEnv())->carregar($this->diretorio . '/nao-existe', [])->textoObrigatorio('DB_USERNAME');
    }

    #[Test]
    public function limitadorBloqueiaAposOMaximoELiberaAposAJanela(): void
    {
        $relogio = new class () implements Relogio {
            public DateTimeImmutable $agora;

            public function __construct()
            {
                $this->agora = new DateTimeImmutable('2026-10-24 10:00:00');
            }

            public function agora(): DateTimeImmutable
            {
                return $this->agora;
            }
        };
        $limitador = new LimitadorEmArquivo($this->diretorio, $relogio, maximoDeFalhas: 2, janelaEmSegundos: 60);

        $limitador->registrarFalha('1.2.3.4');
        self::assertFalse($limitador->estaBloqueado('1.2.3.4'));
        $limitador->registrarFalha('1.2.3.4');
        self::assertTrue($limitador->estaBloqueado('1.2.3.4'));
        self::assertFalse($limitador->estaBloqueado('5.6.7.8'), 'outro cliente não é afetado');

        $relogio->agora = $relogio->agora->modify('+61 seconds');
        self::assertFalse($limitador->estaBloqueado('1.2.3.4'));
    }
}
