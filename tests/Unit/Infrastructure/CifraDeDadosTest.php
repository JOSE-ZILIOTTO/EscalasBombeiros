<?php

declare(strict_types=1);

namespace Escala\Tests\Unit\Infrastructure;

use Escala\Infrastructure\Seguranca\CifraDeDados;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CifraDeDadosTest extends TestCase
{
    #[Test]
    public function cifraEDecifraSemDeixarOTextoVisivelNoBanco(): void
    {
        $cifra = $this->cifra();

        $gravado = $cifra->cifrar('123.456.789-09');

        self::assertStringNotContainsString('456', (string) $gravado);
        self::assertTrue($cifra->estaCifrado((string) $gravado));
        self::assertSame('123.456.789-09', $cifra->decifrar($gravado));
    }

    #[Test]
    public function mesmoValorGeraTextosCifradosDiferentes(): void
    {
        $cifra = $this->cifra();

        // IV aleatório: quem vê o banco não descobre que dois bombeiros têm o mesmo banco/agência
        self::assertNotSame($cifra->cifrar('Banco Exemplo'), $cifra->cifrar('Banco Exemplo'));
    }

    #[Test]
    public function valorAntigoSemCriptografiaEVazioPassamComoEstao(): void
    {
        $cifra = $this->cifra();

        self::assertSame('123.456.789-09', $cifra->decifrar('123.456.789-09'));
        self::assertNull($cifra->decifrar(null));
        self::assertNull($cifra->cifrar(null));
        self::assertSame('', $cifra->cifrar(''));
    }

    #[Test]
    public function valorAdulteradoOuChaveErradaFalhamEmVezDeDevolverLixo(): void
    {
        $gravado = (string) $this->cifra()->cifrar('segredo');
        $outraChave = new CifraDeDados(base64_encode(str_repeat('b', 32)));

        $this->expectException(RuntimeException::class);
        $outraChave->decifrar($gravado);
    }

    #[Test]
    public function chaveComTamanhoErradoERecusada(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CifraDeDados(base64_encode('curta'));
    }

    private function cifra(): CifraDeDados
    {
        return new CifraDeDados(base64_encode(str_repeat('a', 32)));
    }
}
