<?php

declare(strict_types=1);

namespace Escala\Http;

/** Cópia imutável dos dados da requisição; os controllers nunca tocam em $_GET/$_POST. */
final readonly class Requisicao
{
    /**
     * @param array<string, mixed>  $consulta   $_GET
     * @param array<string, mixed>  $corpo      $_POST
     * @param array<string, string> $cabecalhos nomes em minúsculas
     * @param array<string, mixed>  $atributos  preenchidos pelos middlewares (ex.: usuário logado)
     */
    public function __construct(
        public string $metodo,
        private array $consulta = [],
        private array $corpo = [],
        private array $cabecalhos = [],
        public string $ip = '',
        private array $atributos = [],
        /** caminho + query, como chegou (REQUEST_URI) */
        public string $uri = '',
    ) {
    }

    public static function atual(): self
    {
        $cabecalhos = [];
        foreach ($_SERVER as $nome => $valor) {
            if (is_string($valor) && str_starts_with((string) $nome, 'HTTP_')) {
                $cabecalhos[strtolower(str_replace('_', '-', substr((string) $nome, 5)))] = $valor;
            }
        }
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $_GET,
            $_POST,
            $cabecalhos,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            [],
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
        );
    }

    public function ehPost(): bool
    {
        return $this->metodo === 'POST';
    }

    /** Parâmetro da URL; só aceita texto (arrays como ?x[]=1 viram null). */
    public function consulta(string $nome): ?string
    {
        $valor = $this->consulta[$nome] ?? null;
        return is_string($valor) ? $valor : null;
    }

    public function campo(string $nome): ?string
    {
        $valor = $this->corpo[$nome] ?? null;
        return is_string($valor) ? $valor : null;
    }

    /** Campo inteiro positivo; qualquer outra coisa vira 0. */
    public function campoInteiro(string $nome): int
    {
        $valor = filter_var($this->campo($nome), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $valor === false ? 0 : $valor;
    }

    public function consultaInteira(string $nome): int
    {
        $valor = filter_var($this->consulta($nome), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $valor === false ? 0 : $valor;
    }

    /** Checkbox: presente no POST = marcado. */
    public function marcado(string $nome): bool
    {
        return array_key_exists($nome, $this->corpo);
    }

    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhos[strtolower($nome)] ?? null;
    }

    public function atributo(string $nome): mixed
    {
        return $this->atributos[$nome] ?? null;
    }

    public function comAtributo(string $nome, mixed $valor): self
    {
        return new self($this->metodo, $this->consulta, $this->corpo, $this->cabecalhos, $this->ip, [...$this->atributos, $nome => $valor], $this->uri);
    }

    /** @return array<string, mixed> para reexibir um formulário após erro */
    public function corpo(): array
    {
        return $this->corpo;
    }
}
