<?php

/**
 * ============================================================
 * Admin - Gestao de Todos os Relatorios
 * Ver, Editar, Apagar e Adicionar
 * ============================================================
 */

require_once __DIR__ . '/../../includes/header.php';
protegerAdmin();

$titulo = 'Todos os Relatorios';

// Processar criacao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $data = $_POST['data'] ?? date('Y-m-d');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $tags = trim($_POST['tags'] ?? '');

    if ($user_id <= 0 || empty($conteudo)) {
        setFlash('error', 'Utilizador e conteudo sao obrigatorios.');
    } else {
        $id = insert('relatorios', [
            'user_id'  => $user_id,
            'data'     => $data,
            'conteudo' => $conteudo,
            'tags'     => $tags
        ]);
        registrarLog('relatorio_criado_admin', 'relatorios', $id);
        setFlash('success', 'Relatorio criado com sucesso!');
    }
    header('Location: relatorios.php');
    exit;
}

// Processar edicao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id = (int)($_POST['id'] ?? 0);
    $user_id = (int)($_POST['user_id'] ?? 0);
    $data = $_POST['data'] ?? '';
    $conteudo = trim($_POST['conteudo'] ?? '');
    $tags = trim($_POST['tags'] ?? '');

    if ($id > 0 && $user_id > 0 && !empty($conteudo)) {
        update('relatorios', [
            'user_id'  => $user_id,
            'data'     => $data,
            'conteudo' => $conteudo,
            'tags'     => $tags
        ], 'id = :id', ['id' => $id]);
        registrarLog('relatorio_editado', 'relatorios', $id);
        setFlash('success', 'Relatorio atualizado com sucesso!');
    }
    header('Location: relatorios.php');
    exit;
}

// Apagar relatorio
if (isset($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];
    if ($id > 0) {
        delete('relatorios', 'id = ?', [$id]);
        registrarLog('relatorio_apagado', 'relatorios', $id);
        setFlash('success', 'Relatorio apagado com sucesso!');
    }
    header('Location: relatorios.php');
    exit;
}

// Filtros
$filtroUser = $_GET['user'] ?? '';
$busca = $_GET['busca'] ?? '';

$params = [];
$where = "1=1";

if ($filtroUser) {
    $where .= " AND r.user_id = ?";
    $params[] = $filtroUser;
}
if ($busca) {
    $where .= " AND (r.conteudo LIKE ? OR r.tags LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

// Paginacao
$porPagina = 15;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$totalRegistos = fetchOne("SELECT COUNT(*) as total FROM relatorios r WHERE {$where}", $params);
$totalPaginas = ceil($totalRegistos['total'] / $porPagina);

$paramsPaginado = array_merge($params, [$porPagina, $offset]);
$relatorios = fetchAll("SELECT r.*, u.nome as autor FROM relatorios r JOIN utilizadores u ON r.user_id = u.id WHERE {$where} ORDER BY r.data DESC, r.created_at DESC LIMIT ? OFFSET ?", $paramsPaginado);

// Lista de utilizadores para filtros e formularios
$listaUsers = fetchAll("SELECT id, nome FROM utilizadores WHERE ativo = 1 ORDER BY nome");
?>

<div class="page-header">
    <h1><i class="fas fa-folder-open" style="margin-right:12px;color:var(--primary);"></i>Todos os Relatorios</h1>
    <p>Gere todos os relatorios de estudo enviados pelos utilizadores.</p>
</div>

<!-- Botao Novo -->
<div style="margin-bottom:24px;">
    <button class="btn btn-primary" onclick="openModal('modalCriar')">
        <i class="fas fa-plus"></i> Novo Relatorio
    </button>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-body">
        <form method="GET" class="toolbar-filters" style="margin:0;">
            <select name="user" class="form-input" onchange="this.form.submit()" style="width:auto;">
                <option value="">Todos os utilizadores</option>
                <?php foreach ($listaUsers as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo $filtroUser == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="busca" class="form-input" placeholder="Pesquisar conteudo..." value="<?php echo htmlspecialchars($busca); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($filtroUser || $busca): ?>
                <a href="relatorios.php" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista de Relatorios -->
<?php if (empty($relatorios)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-file-lines"></i>
            <h3>Nenhum relatorio encontrado</h3>
        </div>
    </div>
<?php else: ?>
    <div style="display:grid; gap:16px;">
        <?php foreach ($relatorios as $rel): ?>
            <div class="relatorio-card fade-in">
                <div class="relatorio-header">
                    <div class="relatorio-autor">
                        <div class="relatorio-avatar"><?php echo strtoupper(substr($rel['autor'], 0, 2)); ?></div>
                        <div class="relatorio-meta">
                            <h4><?php echo htmlspecialchars($rel['autor']); ?></h4>
                            <span><?php echo formatarData($rel['data']); ?> &bull; #<?php echo $rel['id']; ?></span>
                        </div>
                    </div>
                    <div class="table-actions">
                        <button class="btn-icon btn-edit" onclick='editarRelatorio(<?php echo json_encode($rel); ?>)' title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <a href="?apagar=<?php echo $rel['id']; ?>" class="btn-icon btn-delete confirm-delete" title="Apagar">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="relatorio-conteudo"><?php echo nl2br(htmlspecialchars($rel['conteudo'])); ?></div>
                <?php if ($rel['tags']): ?>
                    <div class="relatorio-tags">
                        <?php foreach (explode(',', $rel['tags']) as $tag): ?>
                            <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginacao -->
    <?php if ($totalPaginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina - 1; ?>&user=<?php echo $filtroUser; ?>&busca=<?php echo urlencode($busca); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
                <a href="?pagina=<?php echo $i; ?>&user=<?php echo $filtroUser; ?>&busca=<?php echo urlencode($busca); ?>" class="page-link <?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($pagina < $totalPaginas): ?>
                <a href="?pagina=<?php echo $pagina + 1; ?>&user=<?php echo $filtroUser; ?>&busca=<?php echo urlencode($busca); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal Criar -->
<div class="modal-overlay" id="modalCriar">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Novo Relatorio</h3>
            <button class="modal-close" onclick="closeModal('modalCriar')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="acao" value="criar">
                <div class="form-group">
                    <label class="form-label">Utilizador <span>*</span></label>
                    <select name="user_id" class="form-input" required>
                        <option value="">Selecionar...</option>
                        <?php foreach ($listaUsers as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Data <span>*</span></label>
                    <input type="date" name="data" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tags</label>
                    <input type="text" name="tags" class="form-input" placeholder="PHP, JavaScript, CSS...">
                </div>
                <div class="form-group">
                    <label class="form-label">Conteudo <span>*</span></label>
                    <textarea name="conteudo" class="form-input" rows="6" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalCriar')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Relatorio</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Editar Relatorio</h3>
            <button class="modal-close" onclick="closeModal('modalEditar')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label class="form-label">Utilizador <span>*</span></label>
                    <select name="user_id" id="editUserId" class="form-input" required>
                        <?php foreach ($listaUsers as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Data <span>*</span></label>
                    <input type="date" name="data" id="editData" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tags</label>
                    <input type="text" name="tags" id="editTags" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Conteudo <span>*</span></label>
                    <textarea name="conteudo" id="editConteudo" class="form-input" rows="6" required></textarea>
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
    function editarRelatorio(rel) {
        document.getElementById('editId').value = rel.id;
        document.getElementById('editUserId').value = rel.user_id;
        document.getElementById('editData').value = rel.data;
        document.getElementById('editTags').value = rel.tags || '';
        document.getElementById('editConteudo').value = rel.conteudo;
        openModal('modalEditar');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>