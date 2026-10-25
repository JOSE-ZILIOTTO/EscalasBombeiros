<?php

declare(strict_types=1);

namespace Escala\Http\View;

use Escala\Domain\Escala\Turno;
use Escala\Domain\Shared\Dinheiro;
use Escala\Http\Seguranca\TokensCsrf;
use InvalidArgumentException;

/** Funções de apresentação usadas pelos templates (escape, CSRF, ícones, etiquetas de turno). */
final class AjudanteDeView
{
    /** Traçados no estilo Lucide (ISC), 24x24; um único conjunto para todas as telas. */
    private const ICONES = [
        'calendario' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'pessoas' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'chave' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'impressora' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'sair' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'ajuda' => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'planilha' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M12 11v8"/>',
        'lua' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'anterior' => '<path d="m15 18-6-6 6-6"/>',
        'proximo' => '<path d="m9 18 6-6-6-6"/>',
        'mais' => '<path d="M5 12h14M12 5v14"/>',
        'editar' => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
        'inativar' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 8 5 5M22 8l-5 5"/>',
        'avancar' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'voltar' => '<path d="m12 19-7-7 7-7M19 12H5"/>',
        'fechar' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'olho' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'apagar' => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'escudo' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
        'baixar' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
    ];

    public function __construct(
        private readonly TokensCsrf $csrf,
        /** Pasta pública do projeto, onde ficam css/, js/, img/ e os ícones. */
        private readonly string $raizPublica,
    ) {
    }

    public function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Caminho de CSS/JS/imagem com ?v= baseado na data de alteração do arquivo: quando o
     * arquivo muda, a URL muda e o navegador baixa a versão nova em vez de usar o cache.
     */
    public function recurso(string $caminho): string
    {
        $versao = @filemtime($this->raizPublica . DIRECTORY_SEPARATOR . $caminho);
        return $this->e($versao === false ? $caminho : "{$caminho}?v={$versao}");
    }

    public function csrfToken(): string
    {
        return $this->csrf->token();
    }

    public function csrfCampo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . $this->e($this->csrf->token()) . '">';
    }

    /** SVG decorativo (aria-hidden); o texto do botão ou o aria-label é que dá o significado. */
    public function icone(string $nome): string
    {
        $tracos = self::ICONES[$nome] ?? throw new InvalidArgumentException("Ícone desconhecido: {$nome}");
        return '<svg class="icone" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $tracos . '</svg>';
    }

    /** Etiqueta de turno (HTML fixo, sem dados do usuário). As classes casam com script.js. */
    public function etiquetaTurno(Turno $turno): string
    {
        return match ($turno) {
            Turno::Diurno => '<span class="turno turno--d" title="Diurno (12h)">D</span>',
            Turno::Noturno => '<span class="turno turno--n" title="Noturno (12h)">N</span>',
            Turno::Integral => '<span class="turno turno--i" title="Integral (24h)">24h</span>',
            Turno::IntegralSubstituto => '<span class="turno turno--sub" title="Integral substituto do Fixo (24h)">SUB</span>',
        };
    }

    public function etiquetaFixo(): string
    {
        return '<span class="turno turno--fixo" title="Fixo pelo ciclo 24x72">Fixo</span>';
    }

    /** R$ 1.250,00 */
    public function dinheiro(Dinheiro $valor): string
    {
        return 'R$ ' . number_format($valor->centavos / 100, 2, ',', '.');
    }

    public function dataBr(string $dataIso): string
    {
        return implode('/', array_reverse(explode('-', $dataIso)));
    }
}
