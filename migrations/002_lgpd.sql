-- Migração 002 — 2026-10-25 — proteção de dados pessoais (LGPD)
-- Rode UMA vez em cada banco (local e produção), depois de fazer backup.
-- Depois desta migração, configure CHAVE_DADOS no .env e rode:
--     php bin/proteger_dados_antigos.php
-- para criptografar os CPFs e dados bancários que já estavam gravados.

-- sem isto, o cliente mysql do Windows lê o arquivo como cp850 e grava acentos corrompidos
SET NAMES utf8mb4;

-- 1) CPF passa a ser gravado criptografado (bem maior que os 14 caracteres do número)
ALTER TABLE bombeiros
    MODIFY cpf VARCHAR(255) NULL COMMENT 'Criptografado (AES-256-GCM, chave CHAVE_DADOS do .env)',
    MODIFY dados_bancarios TEXT NULL COMMENT 'Criptografado (AES-256-GCM, chave CHAVE_DADOS do .env)';

-- 2) Prazo de retenção: data da inativação e marca de dados já apagados
ALTER TABLE bombeiros
    ADD COLUMN IF NOT EXISTS inativado_em DATE NULL COMMENT 'Início do prazo de retenção dos dados pessoais',
    ADD COLUMN IF NOT EXISTS anonimizado_em DATETIME NULL COMMENT 'Dados pessoais apagados nesta data';

-- Quem já estava inativo não tem data registrada: o prazo começa a contar hoje
UPDATE bombeiros SET inativado_em = CURRENT_DATE WHERE ativo = 0 AND inativado_em IS NULL;
