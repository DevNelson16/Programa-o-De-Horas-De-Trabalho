<?php
/**
 * ============================================================
 * Admin - Gestao de Todas as Horas
 * Ver, Editar e Apagar Registos
 * ============================================================
 */

require_once __DIR__ . '/../../includes/header.php';
protegerAdmin();

$titulo = 'Todas as Horas';

// Processar edicao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id = (int)($_POST['id'] ?? 0);
    $data = $_POST['data'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fim = $_POST['hora_fim'] ?? '';
    $total_horas = (float)($_POST['total_horas'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    
    if ($id > 0 && $data && $hora_inicio) {
        // Se hora_fim esta vazia, status = em_andamento
        $status = empty($hora_fim) ? 'em_andamento' : 'concluido';
        
        // Calcular total_horas automaticamente se hora_fim existir
        if (!empty($hora_fim)) {
            $inicio = strtotime($data . ' ' . $hora_inicio);
            $fim = strtotime($data . ' ' . $hora_fim);
            if ($fim > $inicio) {
                $total_horas = round(($fim - $inicio) / 3600, 2);
            }
        }
        
        update('horas', [
            'data'        => $data,
            'hora_inicio' => $hora_inicio,
            'hora_fim'    => $hora_fim ?: null,
            'total_horas' => $total_horas,
            'status'      => $status,
            'descricao'   => $descricao
        ], 'id = :id', ['id' => $id]);
        
        registrarLog('hora_editada', 'horas', $id);
        setFlash('success', 'Registo atualizado com sucesso!');
    }
    header('Location: horas.php');
    exit;
}

// Apagar registo
if (isset($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];
    if ($id > 0) {
        delete('horas', 'id = ?', [$id]);
        registrarLog('hora_apagada', 'horas', $id);
        setFlash('success', 'Registo apagado com sucesso!');
    }
    header('Location: horas.php');
    exit;
}

// Filtros
$filtroUser = $_GET['user'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

$params = [];
$where = "1=1";

if ($filtroUser) {
    $where .= " AND h.user_id = ?";
    $params[] = $filtroUser;
}
if ($filtroStatus) {
    $where .= " AND h.status = ?";
    $params[] = $filtroStatus;
}

// Paginacao
$porPagina = 25;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$totalRegistos = fetchOne("SELECT COUNT(*) as total FROM horas h WHERE {$where}", $params);
$totalPaginas = ceil($totalRegistos['total'] / $porPagina);

$paramsPaginado = array_merge($params, [$porPagina, $offset]);
$registos = fetchAll("SELECT h.*, u.nome as utilizador_nome FROM horas h JOIN utilizadores u ON h.user_id = u.id WHERE {$where} ORDER BY h.data DESC, h.created_at DESC LIMIT ? OFFSET ?", $paramsPaginado);

// Total de horas (todos)
$totalHoras = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas h WHERE {$where}", $params);

// Lista de utilizadores para filtro
$listaUsers = fetchAll("SELECT id, nome FROM utilizadores WHERE ativo = 1 ORDER BY nome");
?>

<div class="page-header">
    <h1><i class="fas fa-list-check" style="margin-right:12px;color:var(--primary);"></i>Todas as Horas</h1>
    <p>Visualiza e gere todos os registos de horas do sistema.</p>
</div>

<!-- Resumo -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-list-ol"></i></div>
        <div class="stat-info"><h3><?php echo $totalRegistos['total']; ?></h3><p>Registos</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($totalHoras['total']); ?></h3><p>Total Horas</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-users"></i></div>
        <div class="stat-info"><h3><?php echo count($listaUsers); ?></h3><p>Utilizadores</p></div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-top:24px;">
    <div class="card-body">
        <form method="GET" class="toolbar-filters" style="margin:0;">
            <select name="user" class="form-input" onchange="this.form.submit()" style="width:auto;">
                <option value="">Todos os utilizadores</option>
                <?php foreach ($listaUsers as $u): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo $filtroUser == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-input" onchange="this.form.submit()" style="width:auto;">
                <option value="">Todos os estados</option>
                <option value="concluido" <?php echo $filtroStatus === 'concluido' ? 'selected' : ''; ?>>Concluido</option>
                <option value="em_andamento" <?php echo $filtroStatus === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                <option value="pausado" <?php echo $filtroStatus === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
            </select>
            <?php if ($filtroUser || $filtroStatus): ?>
            <a href="horas.php" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card" style="margin-top:24px;">
    <div class="card-body" style="padding:0;">
        <?php if (empty($registos)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Nenhum registo encontrado</h3>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilizador</th>
                        <th>Data</th>
                        <th>Inicio</th>
                        <th>Fim</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Descricao</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registos as $reg): ?>
                    <tr>
                        <td>#<?php echo $reg['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($reg['utilizador_nome']); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($reg['data'])); ?></td>
                        <td><?php echo substr($reg['hora_inicio'], 0, 5); ?></td>
                        <td><?php echo $reg['hora_fim'] ? substr($reg['hora_fim'], 0, 5) : '-'; ?></td>
                        <td><strong style="color:var(--primary);"><?php echo formatarHoras($reg['total_horas']); ?></strong></td>
                        <td>
                            <?php if ($reg['status'] === 'concluido'): ?>
                            <span class="badge badge-success">Concluido</span>
                            <?php elseif ($reg['status'] === 'em_andamento'): ?>
                            <span class="badge badge-warning">Em Andamento</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Pausado</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $reg['descricao'] ? htmlspecialchars(substr($reg['descricao'], 0, 40)) . (strlen($reg['descricao']) > 40 ? '...' : '') : '-'; ?></td>
                        <td>
                            <div class="table-actions">
                                <button class="btn-icon btn-edit" onclick='editarHora(<?php echo json_encode($reg); ?>)' title="Editar">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <a href="?apagar=<?php echo $reg['id']; ?>" class="btn-icon btn-delete confirm-delete" title="Apagar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPaginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina > 1): ?>
            <a href="?pagina=<?php echo $pagina - 1; ?>&user=<?php echo $filtroUser; ?>&status=<?php echo $filtroStatus; ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
            <a href="?pagina=<?php echo $i; ?>&user=<?php echo $filtroUser; ?>&status=<?php echo $filtroStatus; ?>" class="page-link <?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($pagina < $totalPaginas): ?>
            <a href="?pagina=<?php echo $pagina + 1; ?>&user=<?php echo $filtroUser; ?>&status=<?php echo $filtroStatus; ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Editar Registo de Horas</h3>
            <button class="modal-close" onclick="closeModal('modalEditar')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label class="form-label">Data <span>*</span></label>
                    <input type="date" name="data" id="editData" class="form-input" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Hora Inicio <span>*</span></label>
                        <input type="time" name="hora_inicio" id="editHoraInicio" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora Fim</label>
                        <input type="time" name="hora_fim" id="editHoraFim" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Descricao</label>
                    <textarea name="descricao" id="editDescricao" class="form-input" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarHora(reg) {
    document.getElementById('editId').value = reg.id;
    document.getElementById('editData').value = reg.data;
    document.getElementById('editHoraInicio').value = reg.hora_inicio;
    document.getElementById('editHoraFim').value = reg.hora_fim || '';
    document.getElementById('editDescricao').value = reg.descricao || '';
    openModal('modalEditar');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
