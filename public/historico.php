<?php
/**
 * ============================================================
 * Pagina de Historico de Horas
 * Visualizar por Dia, Semana e Mes
 * ============================================================
 */

require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$titulo = 'Historico';

// Filtros
$filtro = $_GET['filtro'] ?? 'todos';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

// Construir query
$params = [$user_id];
$where = "h.user_id = ? AND h.status = 'concluido'";

if ($filtro === 'hoje') {
    $where .= " AND h.data = CURDATE()";
} elseif ($filtro === 'semana') {
    $semana = getSemanaAtual();
    $where .= " AND h.data BETWEEN ? AND ?";
    $params[] = $semana['inicio'];
    $params[] = $semana['fim'];
} elseif ($filtro === 'mes') {
    $mes = getMesAtual();
    $where .= " AND h.data BETWEEN ? AND ?";
    $params[] = $mes['inicio'];
    $params[] = $mes['fim'];
} elseif ($data_inicio && $data_fim) {
    $where .= " AND h.data BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
}

// Paginacao
$porPagina = 20;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

// Total de registos
$totalRegistos = fetchOne("SELECT COUNT(*) as total FROM horas h WHERE {$where}", $params);
$totalPaginas = ceil($totalRegistos['total'] / $porPagina);

// Registos
$params[] = $offset;
$params[] = $porPagina;
$registos = fetchAll("SELECT h.*, u.nome as utilizador_nome FROM horas h JOIN utilizadores u ON h.user_id = u.id WHERE {$where} ORDER BY h.data DESC, h.hora_fim DESC LIMIT ? OFFSET ?", $params);

// Totais
$totalHoras = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas h WHERE {$where}", array_slice($params, 0, -2));

// Horas por dia (para grafico)
$horasPorDia = fetchAll("SELECT h.data, SUM(h.total_horas) as total FROM horas h WHERE {$where} GROUP BY h.data ORDER BY h.data DESC LIMIT 30", array_slice($params, 0, -2));
?>

<div class="page-header">
    <h1><i class="fas fa-clock-rotate-left" style="margin-right:12px;color:var(--primary);"></i>Historico de Horas</h1>
    <p>Visualiza todas as tuas horas de programacao registadas.</p>
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
        <div class="stat-icon purple"><i class="fas fa-calculator"></i></div>
        <div class="stat-info"><h3><?php echo $totalRegistos['total'] > 0 ? formatarHoras(round($totalHoras['total'] / $totalRegistos['total'], 2)) : '0h'; ?></h3><p>Media por Sessao</p></div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-top:24px;">
    <div class="card-body">
        <form method="GET" class="toolbar-filters" style="margin:0;">
            <select name="filtro" class="form-input" onchange="this.form.submit()" style="width:auto;">
                <option value="todos" <?php echo $filtro === 'todos' ? 'selected' : ''; ?>>Todos os registos</option>
                <option value="hoje" <?php echo $filtro === 'hoje' ? 'selected' : ''; ?>>Hoje</option>
                <option value="semana" <?php echo $filtro === 'semana' ? 'selected' : ''; ?>>Esta Semana</option>
                <option value="mes" <?php echo $filtro === 'mes' ? 'selected' : ''; ?>>Este Mes</option>
                <option value="custom" <?php echo $filtro === 'custom' ? 'selected' : ''; ?>>Periodo Customizado</option>
            </select>
            
            <?php if ($filtro === 'custom'): ?>
            <input type="date" name="data_inicio" class="form-input" value="<?php echo $data_inicio; ?>" style="width:auto;">
            <span style="color:var(--text-secondary);">ate</span>
            <input type="date" name="data_fim" class="form-input" value="<?php echo $data_fim; ?>" style="width:auto;">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
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
            <p>Utiliza o temporizador para criar o teu primeiro registo.</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Dia</th>
                        <th>Inicio</th>
                        <th>Fim</th>
                        <th>Total</th>
                        <th>Descricao</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registos as $reg): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($reg['data'])); ?></td>
                        <td><?php echo diaSemana($reg['data']); ?></td>
                        <td><?php echo substr($reg['hora_inicio'], 0, 5); ?></td>
                        <td><?php echo $reg['hora_fim'] ? substr($reg['hora_fim'], 0, 5) : '-'; ?></td>
                        <td><strong style="color:var(--primary);"><?php echo formatarHoras($reg['total_horas']); ?></strong></td>
                        <td><?php echo $reg['descricao'] ? htmlspecialchars(substr($reg['descricao'], 0, 50)) . (strlen($reg['descricao']) > 50 ? '...' : '') : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginacao -->
        <?php if ($totalPaginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina > 1): ?>
            <a href="?pagina=<?php echo $pagina - 1; ?>&filtro=<?php echo $filtro; ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
            <a href="?pagina=<?php echo $i; ?>&filtro=<?php echo $filtro; ?>" class="page-link <?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($pagina < $totalPaginas): ?>
            <a href="?pagina=<?php echo $pagina + 1; ?>&filtro=<?php echo $filtro; ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
