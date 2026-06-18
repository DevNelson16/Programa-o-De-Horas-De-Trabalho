<?php
/**
 * ============================================================
 * Admin - Gestao de Utilizadores
 * Criar, Editar, Apagar e Listar
 * ============================================================
 */

require_once __DIR__ . '/../../includes/header.php';
protegerAdmin();

$titulo = 'Utilizadores';

// Processar acoes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Criar utilizador
    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if (empty($nome) || empty($email) || empty($password)) {
            setFlash('error', 'Todos os campos sao obrigatorios.');
        } elseif (strlen($password) < 6) {
            setFlash('error', 'A password deve ter pelo menos 6 caracteres.');
        } else {
            $resultado = registarUtilizador($nome, $email, $password, $role);
            setFlash($resultado['sucesso'] ? 'success' : 'error', $resultado['mensagem']);
        }
        header('Location: utilizadores.php');
        exit;
    }
    
    // Editar utilizador
    if ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $nova_password = $_POST['nova_password'] ?? '';
        
        if ($id <= 0) {
            setFlash('error', 'ID invalido.');
        } else {
            $dados = [
                'nome' => $nome,
                'email' => $email,
                'role' => $role,
                'ativo' => $ativo
            ];
            
            if (!empty($nova_password)) {
                if (strlen($nova_password) < 6) {
                    setFlash('error', 'A password deve ter pelo menos 6 caracteres.');
                    header('Location: utilizadores.php');
                    exit;
                }
                $dados['password'] = password_hash($nova_password, PASSWORD_DEFAULT);
            }
            
            update('utilizadores', $dados, 'id = :id', ['id' => $id]);
            registrarLog('utilizador_editado', 'utilizadores', $id);
            setFlash('success', 'Utilizador atualizado com sucesso!');
        }
        header('Location: utilizadores.php');
        exit;
    }
}

// Apagar utilizador
if (isset($_GET['apagar'])) {
    $id = (int)$_GET['apregar'];
    if ($id > 0 && $id != $_SESSION['user_id']) {
        delete('utilizadores', 'id = ?', [$id]);
        registrarLog('utilizador_apagado', 'utilizadores', $id);
        setFlash('success', 'Utilizador apagado com sucesso!');
    } elseif ($id == $_SESSION['user_id']) {
        setFlash('error', 'Nao podes apagar a tua propria conta.');
    }
    header('Location: utilizadores.php');
    exit;
}

// Listar utilizadores
$utilizadores = fetchAll("SELECT u.*, COALESCE(SUM(h.total_horas), 0) as total_horas FROM utilizadores u LEFT JOIN horas h ON u.id = h.user_id AND h.status = 'concluido' GROUP BY u.id ORDER BY u.created_at DESC");
?>

<div class="page-header">
    <h1><i class="fas fa-users" style="margin-right:12px;color:var(--primary);"></i>Utilizadores</h1>
    <p>Gere todos os utilizadores do sistema.</p>
</div>

<!-- Botao Novo -->
<div style="margin-bottom:24px;">
    <button class="btn btn-primary" onclick="openModal('modalCriar')">
        <i class="fas fa-plus"></i> Novo Utilizador
    </button>
</div>

<!-- Tabela -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Horas</th>
                        <th>Estado</th>
                        <th>Criado</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilizadores as $u): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge badge-warning"><i class="fas fa-shield-halved" style="margin-right:4px;"></i>Admin</span>
                            <?php else: ?>
                            <span class="badge badge-info">User</span>
                            <?php endif; ?>
                        </td>
                        <td><strong style="color:var(--primary);"><?php echo formatarHoras($u['total_horas']); ?></strong></td>
                        <td>
                            <?php if ($u['ativo']): ?>
                            <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <div class="table-actions">
                                <button class="btn-icon btn-edit" onclick='editarUtilizador(<?php echo json_encode($u); ?>)' title="Editar">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="?apagar=<?php echo $u['id']; ?>" class="btn-icon btn-delete confirm-delete" title="Apagar">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar -->
<div class="modal-overlay" id="modalCriar">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Novo Utilizador</h3>
            <button class="modal-close" onclick="closeModal('modalCriar')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="acao" value="criar">
                <div class="form-group">
                    <label class="form-label">Nome <span>*</span></label>
                    <input type="text" name="nome" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password <span>*</span></label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select name="role" class="form-input">
                        <option value="user">Utilizador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalCriar')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Utilizador</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Editar Utilizador</h3>
            <button class="modal-close" onclick="closeModal('modalEditar')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label class="form-label">Nome <span>*</span></label>
                    <input type="text" name="nome" id="editNome" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" name="email" id="editEmail" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nova Password (deixar em branco para manter)</label>
                    <input type="password" name="nova_password" class="form-input" minlength="6" placeholder="Minimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select name="role" id="editRole" class="form-input">
                        <option value="user">Utilizador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="ativo" id="editAtivo" value="1" style="width:18px;height:18px;">
                        <span>Conta ativa</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Alteracoes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarUtilizador(user) {
    document.getElementById('editId').value = user.id;
    document.getElementById('editNome').value = user.nome;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editAtivo').checked = user.ativo == 1;
    openModal('modalEditar');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
