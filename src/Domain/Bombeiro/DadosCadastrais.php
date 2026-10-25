<?php

declare(strict_types=1);

namespace Escala\Domain\Bombeiro;

/** Dados pessoais e de uniforme; não participam de nenhuma regra de escala. */
final readonly class DadosCadastrais
{
    public function __construct(
        public ?Cpf $cpf = null,
        public ?string $enderecoRua = null,
        public ?string $enderecoNumero = null,
        public ?string $enderecoBairro = null,
        public ?string $enderecoCidade = null,
        public ?string $enderecoUf = null,
        public ?string $enderecoCep = null,
        public ?string $telefonePrincipal = null,
        public ?string $contatoEmergenciaNome = null,
        public ?string $contatoEmergenciaFone = null,
        public ?string $dadosBancarios = null,
        public ?string $tamanhoGandola = null,
        public ?string $tamanhoCamiseta = null,
        public ?string $tamanhoCalca = null,
        public ?string $tamanhoCalcado = null,
    ) {
    }

    public function vazio(): bool
    {
        return $this == new self(); // comparação campo a campo: todos nulos
    }
}
