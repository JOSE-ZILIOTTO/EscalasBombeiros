<?php

declare(strict_types=1);

namespace Escala\Application\Exportacao;

/** Nome em maiúsculas com o nome de guerra em negrito; devolve HTML já escapado. */
final class FormatadorDeNomeDestaque
{
    public function formatar(string $nomeCompleto, ?string $nomeGuerra): string
    {
        $nome = $this->escapar(mb_strtoupper($nomeCompleto));
        $guerra = $this->escapar(mb_strtoupper(trim((string) $nomeGuerra)));

        if ($guerra !== '') {
            // palavra inteira: "CRISTIAN" não pode destacar parte de "CRISTIANE"
            $padrao = '/(?<![\p{L}])' . preg_quote($guerra, '/') . '(?![\p{L}])/u';
            if (preg_match($padrao, $nome) === 1) {
                return (string) preg_replace($padrao, '<b>$0</b>', $nome, 1);
            }
            return $nome . ' (<b>' . $guerra . '</b>)';
        }

        $palavras = explode(' ', $nome);
        if (count($palavras) > 1) {
            $ultima = array_pop($palavras);
            return implode(' ', $palavras) . ' <b>' . $ultima . '</b>';
        }
        return '<b>' . $nome . '</b>';
    }

    private function escapar(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}
