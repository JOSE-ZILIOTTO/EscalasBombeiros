-- Modelo: copie para migrations/nomes_de_guerra.local.sql (ignorado pelo Git) e troque pelos
-- nomes reais. Rode depois da migração 001; só preenche quem ainda não tem nome de guerra.

UPDATE bombeiros SET nome_guerra = CASE UPPER(nome_completo)
    WHEN 'MARIA DA SILVA SOUZA' THEN 'SOUZA'
    WHEN 'JOÃO PEREIRA'         THEN 'JOÃO'
    ELSE nome_guerra
END
WHERE nome_guerra IS NULL;

-- Assinatura do rodapé da planilha (também editável pela tela da planilha)
INSERT INTO configuracoes (chave, valor) VALUES
    ('assinatura_nome', 'Nome do responsável'),
    ('assinatura_cargo', 'Coordenador BC'),
    ('assinatura_unidade', 'Unidade – Cidade')
ON DUPLICATE KEY UPDATE valor = valor;
