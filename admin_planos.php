<?php
/**
 * Script para gerenciamento de configurações de planos
 * Este arquivo permite visualizar e editar as configurações de planos no painel administrativo
 */

// Incluir arquivo de conexão com o banco de dados
require_once 'db_connect.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar formulário de atualização de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resposta = array();
    
    // Verificar se é uma requisição AJAX
    if (isset($_POST['acao']) && $_POST['acao'] === 'atualizar_configuracoes') {
        $valorPlanoBasico = isset($_POST['valorPlanoBasico']) ? floatval($_POST['valorPlanoBasico']) : 0;
        $valorConexao = isset($_POST['valorConexao']) ? floatval($_POST['valorConexao']) : 0;
        $valorAtendente = isset($_POST['valorAtendente']) ? floatval($_POST['valorAtendente']) : 0;
        $valorAssistenteGPT = isset($_POST['valorAssistenteGPT']) ? floatval($_POST['valorAssistenteGPT']) : 0;
        
        $conexao = conectarBD();
        $sucesso = true;
        
        // Atualizar cada configuração
        $configuracoes = [
            'valor_plano_basico' => $valorPlanoBasico,
            'valor_conexao' => $valorConexao,
            'valor_atendente' => $valorAtendente,
            'valor_assistente_gpt' => $valorAssistenteGPT
        ];
        
        foreach ($configuracoes as $nome => $valor) {
            $stmt = $conexao->prepare("UPDATE configuracoes_planos SET valor = ?, ultima_atualizacao = NOW() WHERE nome_configuracao = ?");
            $stmt->bind_param("ds", $valor, $nome);
            
            if (!$stmt->execute()) {
                $sucesso = false;
                break;
            }
        }
        
        $conexao->close();
        
        if ($sucesso) {
            $resposta = array(
                'sucesso' => true,
                'mensagem' => 'Configurações atualizadas com sucesso!'
            );
        } else {
            $resposta = array(
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar configurações.'
            );
        }
        
        // Retornar resposta como JSON
        header('Content-Type: application/json');
        echo json_encode($resposta);
        exit;
    }
}

// Obter configurações atuais
$configuracoes = obterConfiguracoesPlanos();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Planos - Administração</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-saira">
    <div class="max-w-4xl mx-auto p-8 font-saira">
        <div class="flex justify-between items-center mb-8">
            <img src="https://static.wixstatic.com/media/f6efe5_9b2f4a503f14416b992b0e481753e309~mv2.png/v1/fill/w_205,h_47,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/f6efe5_9b2f4a503f14416b992b0e481753e309~mv2.png" class="w-48">
            <div class="flex space-x-4">
                <?php if ($_SESSION['is_super_admin']): ?>
                <a href="cadastro-admin.php" class="bg-[#06618E] hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    Cadastrar Administradores
                </a>
                <?php endif; ?>
                <a href="logout.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    Sair
                </a>
            </div>
        </div>

        <h1 class="text-3xl font-bold mb-8 text-[#06618E]">Configuração de Planos</h1>
        
        <div id="mensagem" class="p-4 rounded-lg mb-6 hidden"></div>
        
        <div class="bg-white shadow-md rounded-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-6">Valores Base</h2>
            
            <form id="configForm" class="space-y-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-gray-700 font-medium">Valor do Plano Básico (R$):</label>
                        <input type="number" id="valorPlanoBasico" name="valorPlanoBasico" step="0.01" min="0" 
                               value="<?php echo isset($configuracoes['valor_plano_basico']) ? $configuracoes['valor_plano_basico'] : '100.00'; ?>" 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-gray-700 font-medium">Valor por Conexão WhatsApp (R$):</label>
                        <input type="number" id="valorConexao" name="valorConexao" step="0.01" min="0" 
                               value="<?php echo isset($configuracoes['valor_conexao']) ? $configuracoes['valor_conexao'] : '50.00'; ?>" 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-gray-700 font-medium">Valor por Atendente (R$):</label>
                        <input type="number" id="valorAtendente" name="valorAtendente" step="0.01" min="0" 
                               value="<?php echo isset($configuracoes['valor_atendente']) ? $configuracoes['valor_atendente'] : '30.00'; ?>" 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-gray-700 font-medium">Valor do Assistente GPT (R$):</label>
                        <input type="number" id="valorAssistenteGPT" name="valorAssistenteGPT" step="0.01" min="0" 
                               value="<?php echo isset($configuracoes['valor_assistente_gpt']) ? $configuracoes['valor_assistente_gpt'] : '80.00'; ?>" 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                </div>
                
                <button type="submit" class="bg-[#06618E] hover:bg-indigo-700 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200">
                    Salvar Configurações
                </button>
            </form>
        </div>
        
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-6">Assinaturas Recentes</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Cliente</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Plano</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Valor</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Data</th>
                        </tr>
                    </thead>
                    <tbody id="assinaturasTable">
                        <!-- Dados de assinaturas serão carregados aqui -->
                        <tr>
                            <td colspan="5" class="py-4 px-4 text-center text-gray-500">Carregando assinaturas...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const configForm = document.getElementById('configForm');
            const mensagemEl = document.getElementById('mensagem');
            
            // Função para mostrar mensagens
            function mostrarMensagem(texto, classe) {
                mensagemEl.textContent = texto;
                mensagemEl.className = `p-4 rounded-lg mb-6 ${classe}`;
                mensagemEl.classList.remove('hidden');
                
                setTimeout(() => {
                    mensagemEl.classList.add('hidden');
                }, 3000);
            }
            
            // Processar formulário de configurações
            configForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(configForm);
                formData.append('acao', 'atualizar_configuracoes');
                
                fetch('admin_planos.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        mostrarMensagem(data.mensagem, 'bg-green-100 text-green-700');
                    } else {
                        mostrarMensagem(data.mensagem, 'bg-red-100 text-red-700');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarMensagem('Erro ao processar a requisição. Tente novamente.', 'bg-red-100 text-red-700');
                });
            });
            
            // Carregar assinaturas recentes (implementar função para buscar do banco de dados)
            // Esta função seria implementada em um arquivo PHP separado
        });
    </script>
</body>
</html>