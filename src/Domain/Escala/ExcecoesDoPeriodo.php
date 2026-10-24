<?php

declare(strict_types=1);

namespace Escala\Domain\Escala;

/** Conjunto de exceções de ciclo carregado de uma vez, para não consultar o banco dia a dia. */
final readonly class ExcecoesDoPeriodo
{
    /** @var array<string, true> chave "bombeiroId|Y-m-d" */
    private array $indice;

    /** @param list<array{bombeiroId: int, data: DataEscala}> $excecoes */
    public function __construct(array $excecoes = [])
    {
        $indice = [];
        foreach ($excecoes as $excecao) {
            $indice[self::chave($excecao['bombeiroId'], $excecao['data'])] = true;
        }
        $this->indice = $indice;
    }

    public function possui(int $bombeiroId, DataEscala $data): bool
    {
        return isset($this->indice[self::chave($bombeiroId, $data)]);
    }

    private static function chave(int $bombeiroId, DataEscala $data): string
    {
        return $bombeiroId . '|' . $data->emTexto();
    }
}
