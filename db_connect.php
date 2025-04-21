<?php
/**
 * Arquivo de conexão com o banco de dados MySQL
 * Este arquivo fornece funções para conectar e interagir com o banco de dados
 */

// Configurações de conexão com o banco de dados
$db_host = 'localhost'; // Altere para o host do seu servidor MySQL
$db_name = 'zum_saas';  // Nome do banco de dados criado no arquivo database.sql
$db_user = 'root';      // Altere para seu usuário MySQL
$db_pass = '';          // Altere para sua senha MySQL

/**
 * Estabelece conexão com o banco de dados
 * @return mysqli Objeto de conexão com o banco de dados
 */
function conectarBD() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    $conexao = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Verificar conexão
    if ($conexao->connect_error) {
        die("Falha na conexão: " . $conexao->connect_error);
    }
    
    // Definir charset para UTF-8
    $conexao->set_charset("utf8mb4");
    
    return $conexao;
}

/**
 * Verifica credenciais de login
 * @param string $email Email do usuário
 * @param string $senha Senha do usuário
 * @return array|false Dados do usuário ou false se autenticação falhar
 */
function verificarLogin($email, $senha) {
    $conexao = conectarBD();
    
    $stmt = $conexao->prepare("SELECT id, nome, email, senha, is_super_admin FROM usuarios WHERE email = ? AND status = 'ativo'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // Verificar senha (em produção, use password_verify com senhas hash)
        if ($senha === $usuario['senha']) {
            // Atualizar último acesso
            $stmt = $conexao->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
            $stmt->bind_param("i", $usuario['id']);
            $stmt->execute();
            
            // Registrar log de acesso
            registrarLog($usuario['id'], 'login');
            
            // Remover senha antes de retornar
            unset($usuario['senha']);
            return $usuario;
        }
    }
    
    $conexao->close();
    return false;
}

/**
 * Registra log de acesso
 * @param int $usuario_id ID do usuário
 * @param string $acao Ação realizada
 * @return bool Sucesso ou falha
 */
function registrarLog($usuario_id, $acao) {
    $conexao = conectarBD();
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conexao->prepare("INSERT INTO logs_acesso (usuario_id, ip, acao) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $usuario_id, $ip, $acao);
    $resultado = $stmt->execute();
    
    $conexao->close();
    return $resultado;
}

/**
 * Obtém configurações de planos
 * @return array Configurações de planos
 */
function obterConfiguracoesPlanos() {
    $conexao = conectarBD();
    
    $resultado = $conexao->query("SELECT nome_configuracao, valor FROM configuracoes_planos");
    $configuracoes = [];
    
    while ($row = $resultado->fetch_assoc()) {
        $configuracoes[$row['nome_configuracao']] = $row['valor'];
    }
    
    $conexao->close();
    return $configuracoes;
}

/**
 * Cadastra um novo cliente e sua assinatura
 * @param array $dados_cliente Dados do cliente
 * @param array $dados_assinatura Dados da assinatura
 * @param array $detalhes_plano Detalhes do plano escolhido
 * @return int|false ID da assinatura criada ou false em caso de erro
 */
function cadastrarAssinatura($dados_cliente, $dados_assinatura, $detalhes_plano) {
    $conexao = conectarBD();
    $conexao->begin_transaction();
    
    try {
        // Inserir cliente
        $stmt = $conexao->prepare("INSERT INTO clientes (nome, email, telefone, cpf_cnpj, endereco, cidade, estado, cep) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", 
            $dados_cliente['nome'], 
            $dados_cliente['email'], 
            $dados_cliente['telefone'], 
            $dados_cliente['cpf_cnpj'], 
            $dados_cliente['endereco'], 
            $dados_cliente['cidade'], 
            $dados_cliente['estado'], 
            $dados_cliente['cep']
        );
        $stmt->execute();
        $cliente_id = $conexao->insert_id;
        
        // Inserir assinatura
        $stmt = $conexao->prepare("INSERT INTO assinaturas (cliente_id, data_inicio, data_renovacao, valor_mensal, forma_pagamento, status) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdss", 
            $cliente_id, 
            $dados_assinatura['data_inicio'], 
            $dados_assinatura['data_renovacao'], 
            $dados_assinatura['valor_mensal'], 
            $dados_assinatura['forma_pagamento'],
            $dados_assinatura['status']
        );
        $stmt->execute();
        $assinatura_id = $conexao->insert_id;
        
        // Inserir detalhes do plano
        $stmt = $conexao->prepare("INSERT INTO detalhes_plano (assinatura_id, qtd_conexoes, qtd_atendentes, assistente_gpt) 
                                  VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", 
            $assinatura_id, 
            $detalhes_plano['qtd_conexoes'], 
            $detalhes_plano['qtd_atendentes'], 
            $detalhes_plano['assistente_gpt']
        );
        $stmt->execute();
        
        $conexao->commit();
        $conexao->close();
        return $assinatura_id;
    } catch (Exception $e) {
        $conexao->rollback();
        $conexao->close();
        return false;
    }
}

/**
 * Registra um pagamento
 * @param array $dados_pagamento Dados do pagamento
 * @return int|false ID do pagamento ou false em caso de erro
 */
function registrarPagamento($dados_pagamento) {
    $conexao = conectarBD();
    
    $stmt = $conexao->prepare("INSERT INTO pagamentos (assinatura_id, data_pagamento, valor, status, codigo_transacao, metodo_pagamento) 
                              VALUES (?, NOW(), ?, ?, ?, ?)");
    $stmt->bind_param("idsss", 
        $dados_pagamento['assinatura_id'], 
        $dados_pagamento['valor'], 
        $dados_pagamento['status'], 
        $dados_pagamento['codigo_transacao'], 
        $dados_pagamento['metodo_pagamento']
    );
    
    if ($stmt->execute()) {
        $pagamento_id = $conexao->insert_id;
        
        // Atualizar status da assinatura se pagamento aprovado
        if ($dados_pagamento['status'] === 'aprovado') {
            $stmt = $conexao->prepare("UPDATE assinaturas SET status = 'ativa' WHERE id = ?");
            $stmt->bind_param("i", $dados_pagamento['assinatura_id']);
            $stmt->execute();
        }
        
        $conexao->close();
        return $pagamento_id;
    }
    
    $conexao->close();
    return false;
}

/**
 * Obtém lista de administradores
 * @return array Lista de administradores
 */
function listarAdministradores() {
    $conexao = conectarBD();
    
    $resultado = $conexao->query("SELECT id, nome, email, is_super_admin, data_criacao, ultimo_acesso, status FROM usuarios ORDER BY nome");
    $administradores = [];
    
    while ($row = $resultado->fetch_assoc()) {
        $administradores[] = $row;
    }
    
    $conexao->close();
    return $administradores;
}

/**
 * Cadastra um novo administrador
 * @param array $dados Dados do administrador
 * @return int|false ID do administrador ou false em caso de erro
 */
function cadastrarAdministrador($dados) {
    $conexao = conectarBD();
    
    // Em produção, use password_hash para senhas
    $stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, senha, is_super_admin) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $dados['nome'], $dados['email'], $dados['senha'], $dados['is_super_admin']);
    
    if ($stmt->execute()) {
        $admin_id = $conexao->insert_id;
        $conexao->close();
        return $admin_id;
    }
    
    $conexao->close();
    return false;
}
?>