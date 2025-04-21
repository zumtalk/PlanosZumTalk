# Sistema de Gerenciamento de Planos ZUMTalk

## Configuração do Banco de Dados MySQL

Este projeto agora inclui um banco de dados MySQL para armazenar informações de usuários, administradores, planos e assinaturas. Siga as instruções abaixo para configurar o ambiente:

### Requisitos

- Servidor MySQL (recomendado: MySQL 5.7 ou superior)
- PHP 7.4 ou superior
- Servidor web (Apache, Nginx, etc.)

### Passos para Configuração

1. **Criar o Banco de Dados**

   Execute o script SQL fornecido no arquivo `database.sql` para criar o banco de dados e as tabelas necessárias:

   ```
   mysql -u seu_usuario -p < database.sql
   ```

   Ou importe o arquivo `database.sql` usando uma ferramenta como phpMyAdmin.

2. **Configurar a Conexão**

   Abra o arquivo `db_connect.php` e atualize as configurações de conexão com o banco de dados:

   ```php
   $db_host = 'localhost'; // Altere para o host do seu servidor MySQL
   $db_name = 'zum_saas';  // Nome do banco de dados
   $db_user = 'root';      // Altere para seu usuário MySQL
   $db_pass = '';          // Altere para sua senha MySQL
   ```

3. **Configurar o Servidor Web**

   Certifique-se de que seu servidor web está configurado para executar PHP e que o diretório do projeto está acessível.

### Estrutura do Banco de Dados

O banco de dados inclui as seguintes tabelas:

- **usuarios**: Armazena informações dos administradores do sistema
- **configuracoes_planos**: Armazena os valores base para cálculo dos planos
- **clientes**: Armazena informações dos clientes
- **assinaturas**: Registra as assinaturas ativas dos clientes
- **detalhes_plano**: Armazena as configurações específicas de cada plano assinado
- **pagamentos**: Registra os pagamentos realizados
- **logs_acesso**: Registra os acessos ao sistema

### Migração do Sistema Atual

O sistema atual utiliza localStorage para armazenar dados. Para migrar para o banco de dados:

1. Substitua o arquivo `login.html` pelo novo arquivo `login.php`
2. Atualize as referências em outros arquivos HTML para apontar para `login.php` em vez de `login.html`
3. Crie arquivos PHP adicionais para substituir a lógica JavaScript que atualmente manipula dados no localStorage

### Credenciais Padrão

O sistema já vem com um usuário administrador padrão:

- **Email**: admin@zum.net.br
- **Senha**: Chicotripa@123

### Segurança

Em um ambiente de produção, é altamente recomendável:

1. Usar senhas hash em vez de texto puro (o código já está preparado para isso)
2. Configurar HTTPS para proteger a transmissão de dados
3. Implementar proteção contra injeção SQL (já implementado através de prepared statements)
4. Limitar tentativas de login para prevenir ataques de força bruta

### Suporte

Para dúvidas ou problemas na configuração, entre em contato com o suporte técnico.