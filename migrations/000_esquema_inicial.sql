-- Esquema inicial (instalação nova). Só estrutura, sem dados.
-- Em um banco que já tem as tabelas antigas, rode a 001 em vez desta.
-- Compatível com MariaDB 10.4+ / MySQL 8.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `bombeiros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_completo` varchar(255) NOT NULL,
  `nome_guerra` varchar(60) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL COMMENT 'Formato 000.000.000-00',
  `endereco` varchar(255) DEFAULT NULL COMMENT 'Endereço residencial completo',
  `telefone` varchar(20) DEFAULT NULL COMMENT 'Número de telefone principal',
  `telefone_emergencia` varchar(20) DEFAULT NULL COMMENT 'Contato de emergência (nome e telefone)',
  `tipo` enum('BC','Fixo') NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `fixo_ref_data` date DEFAULT NULL,
  `fixo_ref_dia_ciclo` tinyint(1) DEFAULT NULL CHECK (`fixo_ref_dia_ciclo` between 1 and 4),
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `endereco_rua` varchar(255) DEFAULT NULL,
  `endereco_numero` varchar(20) DEFAULT NULL,
  `endereco_bairro` varchar(100) DEFAULT NULL,
  `endereco_cidade` varchar(100) DEFAULT NULL,
  `endereco_uf` varchar(2) DEFAULT NULL,
  `endereco_cep` varchar(10) DEFAULT NULL,
  `telefone_principal` varchar(20) DEFAULT NULL,
  `contato_emergencia_nome` varchar(255) DEFAULT NULL,
  `contato_emergencia_fone` varchar(20) DEFAULT NULL,
  `dados_bancarios` text DEFAULT NULL COMMENT 'Ex: Banco XPTO, Ag: 0001, C/C: 12345-6',
  `tamanho_gandola` varchar(20) DEFAULT NULL,
  `tamanho_camiseta` varchar(20) DEFAULT NULL,
  `tamanho_calca` varchar(20) DEFAULT NULL,
  `tamanho_calcado` varchar(10) DEFAULT NULL,
  `banco_nome` varchar(100) DEFAULT NULL COMMENT 'Nome do Banco',
  `banco_agencia` varchar(20) DEFAULT NULL COMMENT 'Número da Agência (com dígito, se houver)',
  `banco_conta` varchar(30) DEFAULT NULL COMMENT 'Número da Conta Corrente/Poupança (com dígito)',
  `banco_pix` varchar(100) DEFAULT NULL COMMENT 'Chave PIX principal (CPF, e-mail, telefone, aleatória)',
  PRIMARY KEY (`id`),
  KEY `idx_bombeiros_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo` enum('admin','bc') NOT NULL,
  `bombeiro_id` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `uq_usuarios_bombeiro` (`bombeiro_id`),
  CONSTRAINT `fk_usuarios_bombeiro` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plantoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bombeiro_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('D','N','I','I_SUB') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bombeiro_data_turno` (`bombeiro_id`,`data`,`turno`),
  KEY `idx_data` (`data`),
  CONSTRAINT `plantoes_ibfk_1` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `excecoes_ciclo_fixo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bombeiro_id` int(11) NOT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_excecao` (`bombeiro_id`,`data`),
  CONSTRAINT `excecoes_ciclo_fixo_ibfk_1` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
