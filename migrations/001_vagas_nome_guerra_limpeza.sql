-- Migração 001 — 2026-10-24
-- Rode UMA vez em cada banco (local e produção), depois de fazer backup.
-- Compatível com MariaDB 10.4+ / MySQL 8.

-- 1) Turno I_SUB (substituto integral do Fixo).
--    O código já usava 'I_SUB', mas o ENUM não aceitava: o MySQL gravava turno vazio ('').
ALTER TABLE plantoes
    MODIFY turno ENUM('D', 'N', 'I', 'I_SUB') NOT NULL;

-- Plantões que foram gravados com turno vazio eram tentativas de I_SUB
UPDATE plantoes SET turno = 'I_SUB' WHERE turno = '';

-- 2) Vínculo entre o login (usuarios) e o bombeiro — usado pelo portal para saber "quais plantões são meus"
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS bombeiro_id INT NULL AFTER tipo,
    ADD UNIQUE KEY IF NOT EXISTS uq_usuarios_bombeiro (bombeiro_id),
    ADD CONSTRAINT fk_usuarios_bombeiro FOREIGN KEY IF NOT EXISTS (bombeiro_id)
        REFERENCES bombeiros (id) ON DELETE SET NULL;

-- 3) Nome de guerra: parte do nome destacada em negrito na planilha exportada.
--    Substitui a lista de nomes que ficava fixa em exportar_tabela_formatada.php.
ALTER TABLE bombeiros
    ADD COLUMN IF NOT EXISTS nome_guerra VARCHAR(60) NULL AFTER nome_completo;

-- Os nomes de guerra são dados pessoais e não ficam no repositório: preencha-os pela
-- tela de edição do bombeiro ou rode um arquivo local (veja migrations/nomes_de_guerra.exemplo.sql).
-- Sem nome de guerra, a planilha destaca o último nome.

-- 4) Tabela sem uso no código (chat nunca implementado)
DROP TABLE IF EXISTS chat_mensagens;
