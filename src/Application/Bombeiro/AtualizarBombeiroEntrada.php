<?php

declare(strict_types=1);

namespace Escala\Application\Bombeiro;

/**
 * Campos do formulário "Editar Bombeiro", ainda como texto bruto.
 * CPF e dados bancários ficam ocultos na tela: null = não enviado (mantém o atual), '' = apagar.
 */
final readonly class AtualizarBombeiroEntrada
{
    public function __construct(
        public int $id,
        public string $nomeCompleto,
        public string $tipo,
        public bool $ativo,
        public ?string $nomeGuerra = null,
        public ?string $cpf = null,
        public ?string $fixoRefData = null,
        public ?string $fixoRefDiaCiclo = null,
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
}
