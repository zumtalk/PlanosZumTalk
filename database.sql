-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS zum_saas;
USE zum_saas;

-- Tabela de usuários (administradores)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    is_super_admin BOOLEAN DEFAULT FALSE,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso DATETIME,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
);

-- Inserir o super admin padrão
INSERT INTO usuarios (nome, email, senha, is_super_admin) 
VALUES ('Administrador', 'admin@zum.net.br', 'Chicotripa@123', TRUE);

-- Tabela de configurações de planos
CREATE TABLE IF NOT EXISTS configuracoes_planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_configuracao VARCHAR(50) NOT NULL UNIQUE,
    valor DECIMAL(10,2) NOT NULL,
    descricao VARCHAR(255),
    ultima_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT INTO configuracoes_planos (nome_configuracao, valor, descricao) VALUES
('valor_plano_basico', 100.00, 'Valor base do plano'),
('valor_conexao', 50.00, 'Valor por conexão WhatsApp adicional'),
('valor_atendente', 30.00, 'Valor por atendente adicional'),
('valor_assistente_gpt', 80.00, 'Valor do assistente GPT');

-- Tabela de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    cpf_cnpj VARCHAR(20) UNIQUE,
    endereco VARCHAR(255),
    cidade VARCHAR(100),
    estado CHAR(2),
    cep VARCHAR(10),
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de assinaturas
CREATE TABLE IF NOT EXISTS assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_renovacao DATE NOT NULL,
    status ENUM('ativa', 'cancelada', 'pendente', 'atrasada') DEFAULT 'pendente',
    valor_mensal DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('cartao', 'boleto', 'pix') NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabela de detalhes do plano assinado
CREATE TABLE IF NOT EXISTS detalhes_plano (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assinatura_id INT NOT NULL,
    qtd_conexoes INT NOT NULL DEFAULT 1,
    qtd_atendentes INT NOT NULL DEFAULT 3,
    assistente_gpt BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id)
);

-- Tabela de pagamentos
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assinatura_id INT NOT NULL,
    data_pagamento DATETIME NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    status ENUM('aprovado', 'recusado', 'pendente', 'estornado') NOT NULL,
    codigo_transacao VARCHAR(100),
    metodo_pagamento VARCHAR(50) NOT NULL,
    FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id)
);

-- Tabela de logs de acesso
CREATE TABLE IF NOT EXISTS logs_acesso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45),
    acao VARCHAR(100) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Índices para otimização de consultas
CREATE INDEX idx_cliente_email ON clientes(email);
CREATE INDEX idx_assinatura_cliente ON assinaturas(cliente_id);
CREATE INDEX idx_pagamento_assinatura ON pagamentos(assinatura_id);
CREATE INDEX idx_logs_usuario ON logs_acesso(usuario_id);