<?php

declare(strict_types=1);

namespace Escala\Http;

/**
 * Contrato comum de controllers e middlewares: cada middleware envolve outro
 * Manipulador (Decorator), então autenticação, CSRF e erros se combinam por rota.
 */
interface Manipulador
{
    public function tratar(Requisicao $requisicao): Resposta;
}
