<?php
/**
 * ============================================================
 * Pagina de Perfil do Utilizador
 * ============================================================
 */

require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$titulo = 'Perfil';

// Buscar dados do utilizador
$user = getUtilizadorAtual();

// Processar atualizacao de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'atualizar_perfil') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($nome) || empty($email)) {
            setFlash('error', 'Nome e email sao obrigatorios.');
        } else {
            // Verificar se email ja existe para outro utilizador
            $existe = fetchOne("SELECT id FROM utilizadores WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existe) {
                setFlash('error', 'Este email ja esta a ser utilizado por outro utilizador.');
            } else {
                update('utilizadores', [
                    'nome' => $nome,
                    'email' => $email
                ], 'id = :id', ['id' => $user_id]);
                
                // Atualizar sessao
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_email'] = $email;
                
                registrarLog('perfil_atualizado', 'utilizadores', $user_id);
                setFlash('success', 'Perfil atualizado com sucesso!');
                header('Location: perfil.php');
                exit;
            }
        }
    }
    
    // Alterar password
    if ($_POST['acao'] === 'alterar_password') {
        $password_atual = $_POST['password_atual'] ?? '';
        $nova_password = $_POST['nova_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        
        if (empty($password_atual) || empty($nova_password) || empty($confirmar_password)) {
            setFlash('error', 'Todos os campos de password sao obrigatorios.');
        } elseif ($nova_password !== $confirmar_password) {
            setFlash('error', 'A nova password e a confirmacao nao coincidem.');
        } elseif (strlen($nova_password) < 6) {
            setFlash('error', 'A nova password deve ter pelo menos 6 caracteres.');
        } elseif (!password_verify($password_atual, $user['password'])) {
            setFlash('error', 'Password atual incorreta.');
        } else {
            $hash = password_hash($nova_password, PASSWORD_DEFAULT);
            update('utilizadores', ['password' => $hash], 'id = :id', ['id' => $user_id]);
            
            registrarLog('password_alterada', 'utilizadores', $user_id);
            setFlash('success', 'Password alterada com sucesso!');
            header('Location: perfil.php');
            exit;
        }
    }
}

// Estatisticas do utilizador
$stats = getEstatisticasHoras($user_id);
$totalRelatorios = fetchOne("SELECT COUNT(*) as total FROM relatorios WHERE user_id = ?", [$user_id]);
?>

<div class="page-header">
    <h1><i class="fas fa-user" style="margin-right:12px;color:var(--primary);"></i>Perfil</h1>
    <p>Gere as tuas informacoes de conta e estatisticas pessoais.</p>
</div>

<div class="grid-2">
    <!-- Dados do Perfil -->
    <div class="card fade-in">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-id-card" style="margin-right:8px;color:var(--primary);"></i>Dados do Perfil</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="acao" value="atualizar_perfil">
                
                <div class="form-group">
                    <label class="form-label" for="nome">Nome Completo <span>*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="nome" name="nome" class="form-input" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email <span>*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Conta</label>
                    <input type="text" class="form-input" value="<?php echo $user['role'] === 'admin' ? 'Administrador' : 'Programador'; ?>" disabled style="background:var(--gray-100);">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Membro desde</label>
                    <input type="text" class="form-input" value="<?php echo formatarData(date('Y-m-d', strtotime($user['created_at']))); ?>" disabled style="background:var(--gray-100);">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Alteracoes
                </button>
            </form>
        </div>
    </div>
    
    <!-- Coluna da Direita -->
    <div>
        <!-- Estatisticas -->
        <div class="card fade-in" style="margin-bottom:24px;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-pie" style="margin-right:8px;color:var(--primary);"></i>As Tuas Estatisticas</h2>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div style="text-align:center; padding:16px; background:var(--gray-50); border-radius:var(--radius-lg);">
                        <div style="font-size:var(--font-size-2xl); font-weight:700; color:var(--primary);"><?php echo formatarHoras($stats['total']); ?></div>
                        <div style="font-size:var(--font-size-xs); color:var(--text-secondary); margin-top:4px;">Total de Horas</div>
                    </div>
                    <div style="text-align:center; padding:16px; background:var(--gray-50); border-radius:var(--radius-lg);">
                        <div style="font-size:var(--font-size-2xl); font-weight:700; color:var(--success);"><?php echo $totalRelatorios['total']; ?></div>
                        <div style="font-size:var(--font-size-xs); color:var(--text-secondary); margin-top:4px;">Relatorios</div>
                    </div>
                    <div style="text-align:center; padding:16px; background:var(--gray-50); border-radius:var(--radius-lg);">
                        <div style="font-size:var(--font-size-2xl); font-weight:700; color:var(--info);"><?php echo formatarHoras($stats['mes']); ?></div>
                        <div style="font-size:var(--font-size-xs); color:var(--text-secondary); margin-top:4px;">Este Mes</div>
                    </div>
                    <div style="text-align:center; padding:16px; background:var(--gray-50); border-radius:var(--radius-lg);">
                        <div style="font-size:var(--font-size-2xl); font-weight:700; color:var(--warning);"><?php echo formatarHoras($stats['media']); ?></div>
                        <div style="font-size:var(--font-size-xs); color:var(--text-secondary); margin-top:4px;">Media Diaria</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alterar Password -->
        <div class="card fade-in">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-lock" style="margin-right:8px;color:var(--primary);"></i>Alterar Password</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="acao" value="alterar_password">
                    
                    <div class="form-group">
                        <label class="form-label" for="password_atual">Password Atual <span>*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="password" id="password_atual" name="password_atual" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="nova_password">Nova Password <span>*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="nova_password" name="nova_password" class="form-input" required minlength="6">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirmar_password">Confirmar Nova Password <span>*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirmar_password" name="confirmar_password" class="form-input" required minlength="6">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Alterar Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
