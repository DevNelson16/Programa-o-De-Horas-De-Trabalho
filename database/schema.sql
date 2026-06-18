-- ============================================================
-- Sistema de Controlo de Horas de Programacao
-- Base de Dados MySQL
-- ============================================================

-- Criar base de dados
CREATE DATABASE IF NOT EXISTS programacao_horas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE programacao_horas;

-- ============================================================
-- Tabela: utilizadores
-- ============================================================
CREATE TABLE IF NOT EXISTS utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    foto VARCHAR(255) DEFAULT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: horas (registo de horas de programacao)
-- ============================================================
CREATE TABLE IF NOT EXISTS horas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    data DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME DEFAULT NULL,
    total_horas DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('em_andamento', 'pausado', 'concluido') DEFAULT 'em_andamento',
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    INDEX idx_data (data),
    INDEX idx_user_data (user_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: relatorios (relatorios de estudo)
-- ============================================================
CREATE TABLE IF NOT EXISTS relatorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    data DATE NOT NULL,
    conteudo TEXT NOT NULL,
    tags VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    INDEX idx_data (data),
    INDEX idx_user_data (user_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: logs (auditoria opcional)
-- ============================================================
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    acao VARCHAR(100) NOT NULL,
    tabela_afetada VARCHAR(50) DEFAULT NULL,
    registro_id INT DEFAULT NULL,
    detalhes TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilizadores(id) ON DELETE SET NULL,
    INDEX idx_acao (acao),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Dados Iniciais
-- ============================================================

-- Utilizador Admin padrao (password: admin123)
INSERT IGNORE INTO utilizadores (nome, email, password, role) VALUES
('Administrador', 'admin@sistema.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Utilizador de teste (password: user123)
INSERT IGNORE INTO utilizadores (nome, email, password, role) VALUES
('Utilizador Teste', 'user@sistema.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Utilizador esposa (password: user123)
INSERT IGNORE INTO utilizadores (nome, email, password, role) VALUES
('Esposa', 'esposa@sistema.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Dados de exemplo - horas
INSERT IGNORE INTO horas (user_id, data, hora_inicio, hora_fim, total_horas, status, descricao) VALUES
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', '12:30:00', 3.50, 'concluido', 'Estudo de PHP e MySQL'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '14:00:00', '17:00:00', 3.00, 'concluido', 'Desenvolvimento de projeto'),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', '13:00:00', 3.00, 'concluido', 'Aprendizagem de JavaScript'),
(2, CURDATE(), '09:00:00', '11:30:00', 2.50, 'concluido', 'Revisao de codigo'),
(3, CURDATE(), '09:30:00', '12:00:00', 2.50, 'concluido', 'Pratica de CSS Grid');

-- Dados de exemplo - relatorios
INSERT IGNORE INTO relatorios (user_id, data, conteudo, tags) VALUES
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Hoje estudei PHP orientado a objetos. Aprendi sobre classes, heranca e interfaces. Pratiquei com exercicios de CRUD. Proximos passos: estudar MVC e frameworks.', 'PHP, POO, MySQL'),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Estudei JavaScript assincrono. Promises, async/await e fetch API. Fiz exercicios praticos com chamadas a APIs publicas.', 'JavaScript, Async, API'),
(2, CURDATE(), 'Revisao de conceitos de base de dados. Normalizacao, indices e queries otimizadas. Preparacao para projeto final.', 'MySQL, Bases de Dados');