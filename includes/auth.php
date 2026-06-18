<?php
/**
 * ============================================================
 * Sistema de Autenticacao
 * Login, Registo, Verificacao de Sessao e Permissoes
 * ============================================================
 */

require_once __DIR__ . '/db.php';

/**
 * Verifica se o utilizador esta autenticado
 * @return bool
 */
function estaAutenticado(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Verifica se o utilizador e administrador
 * @return bool
 */
function eAdmin(): bool {
    return estaAutenticado() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redireciona para uma pagina
 * @param string $url Caminho relativo
 */
function redirecionar(string $url): void {
    header("Location: " . APP_URL . "/public/" . $url);
    exit;
}

/**
 * Faz login do utilizador
 * @param string $email Email do utilizador
 * @param string $password Password em texto plano
 * @return array ['sucesso' => bool, 'mensagem' => string]
 */
function fazerLogin(string $email, string $password): array {
    $user = fetchOne("SELECT * FROM utilizadores WHERE email = ? AND ativo = 1", [$email]);
    
    if (!$user) {
        return ['sucesso' => false, 'mensagem' => 'Email ou password incorretos.'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['sucesso' => false, 'mensagem' => 'Email ou password incorretos.'];
    }
    
    // Guardar dados na sessao
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nome']  = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    
    // Registar log
    query("INSERT INTO logs (user_id, acao, tabela_afetada, detalhes) VALUES (?, 'login', 'utilizadores', 'Login bem-sucedido')", [$user['id']]);
    
    return ['sucesso' => true, 'mensagem' => 'Login efetuado com sucesso!'];
}

/**
 * Faz logout do utilizador
 */
function fazerLogout(): void {
    if (isset($_SESSION['user_id'])) {
        query("INSERT INTO logs (user_id, acao, tabela_afetada, detalhes) VALUES (?, 'logout', 'utilizadores', 'Logout efetuado')", [$_SESSION['user_id']]);
    }
    
    session_unset();
    session_destroy();
}

/**
 * Regista um novo utilizador (apenas admin pode criar)
 * @param string $nome Nome completo
 * @param string $email Email
 * @param string $password Password em texto plano
 * @param string $role Role (admin ou user)
 * @return array ['sucesso' => bool, 'mensagem' => string]
 */
function registarUtilizador(string $nome, string $email, string $password, string $role = 'user'): array {
    // Verificar se email ja existe
    $existe = fetchOne("SELECT id FROM utilizadores WHERE email = ?", [$email]);
    if ($existe) {
        return ['sucesso' => false, 'mensagem' => 'Este email ja esta registado.'];
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        insert('utilizadores', [
            'nome'     => $nome,
            'email'    => $email,
            'password' => $hash,
            'role'     => $role
        ]);
        
        return ['sucesso' => true, 'mensagem' => 'Utilizador registado com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao registar: ' . $e->getMessage()];
    }
}

/**
 * Protege pagina - redireciona para login se nao autenticado
 */
function protegerPagina(): void {
    if (!estaAutenticado()) {
        redirecionar('login.php');
    }
}

/**
 * Protege pagina de admin - redireciona se nao for admin
 */
function protegerAdmin(): void {
    protegerPagina();
    if (!eAdmin()) {
        redirecionar('index.php');
    }
}

/**
 * Obtem os dados do utilizador atual
 * @return array|null
 */
function getUtilizadorAtual(): ?array {
    if (!estaAutenticado()) return null;
    return fetchOne("SELECT * FROM utilizadores WHERE id = ?", [$_SESSION['user_id']]);
}

/**
 * CSRF Token - protecao contra ataques CSRF
 * @return string
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica o CSRF token
 * @return bool
 */
function verificarCsrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
