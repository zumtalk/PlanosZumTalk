<?php
/**
 * Script de autenticação para o sistema
 * Este arquivo processa o login de usuários administradores
 */

// Incluir arquivo de conexão com o banco de dados
require_once 'db_connect.php';

// Iniciar sessão
session_start();

// Verificar se já está logado
if (isset($_SESSION['usuario_id'])) {
    // Redirecionar para a página de administração
    header('Location: admin.html');
    exit;
}

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    $resposta = array();
    
    // Validar campos
    if (empty($email) || empty($senha)) {
        $resposta = array(
            'sucesso' => false,
            'mensagem' => 'Por favor, preencha todos os campos.'
        );
    } else {
        // Verificar credenciais no banco de dados
        $usuario = verificarLogin($email, $senha);
        
        if ($usuario) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['is_super_admin'] = $usuario['is_super_admin'];
            
            $resposta = array(
                'sucesso' => true,
                'is_super_admin' => $usuario['is_super_admin'],
                'mensagem' => 'Login realizado com sucesso!'
            );
        } else {
            // Login falhou
            $resposta = array(
                'sucesso' => false,
                'mensagem' => 'Usuário ou senha incorretos.'
            );
        }
    }
    
    // Retornar resposta como JSON
    header('Content-Type: application/json');
    echo json_encode($resposta);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Administração</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-saira">
    <div class="max-w-md mx-auto p-8 font-saira min-h-screen flex flex-col justify-center">
        <img src="https://static.wixstatic.com/media/f6efe5_9b2f4a503f14416b992b0e481753e309~mv2.png/v1/fill/w_205,h_47,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/f6efe5_9b2f4a503f14416b992b0e481753e309~mv2.png" class="w-48 mx-auto mb-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-[#06618E]">Acesso Administrativo</h1>
        
        <div class="bg-white rounded-xl shadow-md p-8 space-y-6">
            <div id="mensagem" class="p-4 rounded-lg hidden"></div>
            
            <form id="loginForm" method="post">
                <div class="space-y-2">
                    <label class="block text-gray-700 font-medium">Usuário:</label>
                    <input type="text" id="usuario" name="email" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                
                <div class="space-y-2 mt-4">
                    <label class="block text-gray-700 font-medium">Senha:</label>
                    <input type="password" id="senha" name="senha" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                
                <button type="submit" id="loginBtn" class="w-full bg-[#06618E] hover:bg-indigo-700 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 mt-6">
                    Entrar
                </button>
            </form>
        </div>

        <a href="planos.html" class="block mt-8 text-center text-gray-600 hover:text-gray-800 transition-colors">
            Voltar para Planos
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const mensagemEl = document.getElementById('mensagem');
            
            // Função para mostrar mensagens
            function mostrarMensagem(texto, classe) {
                mensagemEl.textContent = texto;
                mensagemEl.className = `p-4 rounded-lg ${classe}`;
                mensagemEl.classList.remove('hidden');
                
                setTimeout(() => {
                    mensagemEl.classList.add('hidden');
                }, 3000);
            }
            
            // Processar formulário
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(loginForm);
                
                fetch('login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        // Login bem-sucedido
                        mostrarMensagem(data.mensagem, 'bg-green-100 text-green-700');
                        
                        // Armazenar informações na sessão (não mais no localStorage)
                        setTimeout(() => {
                            window.location.href = 'admin.html';
                        }, 1000);
                    } else {
                        // Login falhou
                        mostrarMensagem(data.mensagem, 'bg-red-100 text-red-700');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarMensagem('Erro ao processar o login. Tente novamente.', 'bg-red-100 text-red-700');
                });
            });
        });
    </script>
</body>
</html>