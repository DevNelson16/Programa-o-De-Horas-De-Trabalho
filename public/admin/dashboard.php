<?php
/**
 * ============================================================
 * Admin Dashboard
 * Estatisticas Globais e Gestao
 * ============================================================
 */

require_once __DIR__ . '/../../includes/header.php';
protegerAdmin();

$titulo = 'Admin Dashboard';

// Estatisticas globais
$global = getEstatisticasGlobais();

// Horas por dia da semana atual (grafico)
$semana = getSemanaAtual();
$diasSemana = [];
$valoresSemana = [];
for ($i = 0; $i < 7; $i++) {
    $data = date('Y-m-d', strtotime($semana['inicio'] . ' +' . $i . ' days'));
    $diasSemana[] = diaSemana($data);
    $result = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE data = ? AND status = 'concluido'", [$data]);
    $valoresSemana[] = (float) $result['total'];
}

// Horas por mes (ultimos 6 meses)
$meses = [];
$valoresMeses = [];
for ($i = 5; $i >= 0; $i--) {
    $ano = date('Y', strtotime("-{$i} months"));
    $mes = date('m', strtotime("-{$i} months"));
    $meses[] = date('M Y', strtotime("-{$i} months"));
    $result = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE YEAR(data) = ? AND MONTH(data) = ? AND status = 'concluido'", [$ano, $mes]);
    $valoresMeses[] = (float) $result['total'];
}

// Ultimos 10 registos
$ultimosRegistos = fetchAll("SELECT h.*, u.nome as utilizador_nome FROM horas h JOIN utilizadores u ON h.user_id = u.id WHERE h.status = 'concluido' ORDER BY h.created_at DESC LIMIT 10");

// Ultimos 5 relatorios
$ultimosRelatorios = fetchAll("SELECT r.*, u.nome as autor FROM relatorios r JOIN utilizadores u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5");

// Utilizadores com mais horas este mes
$topUtilizadores = fetchAll("SELECT u.nome, COALESCE(SUM(h.total_horas), 0) as total FROM utilizadores u LEFT JOIN horas h ON u.id = h.user_id AND h.status = 'concluido' AND h.data BETWEEN ? AND ? WHERE u.ativo = 1 GROUP BY u.id ORDER BY total DESC LIMIT 5", [$semana['inicio'], $semana['fim']]);
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line" style="margin-right:12px;color:var(--primary);"></i>Admin Dashboard</h1>
    <p>Visao geral de todas as horas e relatorios do sistema.</p>
</div>

<!-- Estatisticas Globais -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info"><h3><?php echo $global['utilizadores']; ?></h3><p>Utilizadores</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($global['semana']); ?></h3><p>Horas Esta Semana</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($global['mes']); ?></h3><p>Horas Este Mes</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($global['media']); ?></h3><p>Media Diaria</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-file-lines"></i></div>
        <div class="stat-info"><h3><?php echo $global['relatorios']; ?></h3><p>Relatorios Este Mes</p></div>
    </div>
</div>

<div class="grid-2" style="margin-top:24px;">
    <!-- Grafico Semanal -->
    <div class="card fade-in">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-chart-bar" style="margin-right:8px;color:var(--primary);"></i>Horas por Dia - Semana Atual</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="graficoSemana"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Grafico Mensal -->
    <div class="card fade-in">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-chart-area" style="margin-right:8px;color:var(--primary);"></i>Horas por Mes - Ultimos 6 Meses</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="graficoMeses"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-top:24px;">
    <!-- Top Utilizadores -->
    <div class="card fade-in">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-trophy" style="margin-right:8px;color:var(--warning);"></i>Top Programadores - Semana</h2>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($topUtilizadores)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>Sem dados</h3>
            </div>
            <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Utilizador</th><th>Horas</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topUtilizadores as $i => $u): ?>
                        <tr>
                            <td><strong style="color:var(--primary);">#<?php echo $i + 1; ?></strong></td>
                            <td><?php echo htmlspecialchars($u['nome']); ?></td>
                            <td><strong><?php echo formatarHoras($u['total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Ultimos Registos -->
    <div class="card fade-in">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-clock" style="margin-right:8px;color:var(--primary);"></i>Ultimos Registos</h2>
            <a href="horas.php" class="btn btn-sm btn-outline">Ver Tudo</a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr><th>Utilizador</th><th>Data</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosRegistos as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['utilizador_nome']); ?></td>
                            <td><?php echo date('d/m', strtotime($reg['data'])); ?></td>
                            <td><strong style="color:var(--primary);"><?php echo formatarHoras($reg['total_horas']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ultimos Relatorios -->
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-folder-open" style="margin-right:8px;color:var(--primary);"></i>Ultimos Relatorios</h2>
        <a href="relatorios.php" class="btn btn-sm btn-outline">Ver Todos</a>
    </div>
    <div class="card-body">
        <div style="display:grid; gap:16px;">
            <?php foreach ($ultimosRelatorios as $rel): ?>
            <div class="relatorio-card" style="margin:0;">
                <div class="relatorio-header">
                    <div class="relatorio-autor">
                        <div class="relatorio-avatar"><?php echo strtoupper(substr($rel['autor'], 0, 2)); ?></div>
                        <div class="relatorio-meta">
                            <h4><?php echo htmlspecialchars($rel['autor']); ?></h4>
                            <span><?php echo formatarData($rel['data']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="relatorio-conteudo"><?php echo nl2br(htmlspecialchars(substr($rel['conteudo'], 0, 150))); ?><?php echo strlen($rel['conteudo']) > 150 ? '...' : ''; ?></div>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grafico Semanal
    new Chart(document.getElementById('graficoSemana'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($diasSemana); ?>,
            datasets: [{
                label: 'Horas',
                data: <?php echo json_encode($valoresSemana); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.7)',
                borderColor: '#4f46e5',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => v + 'h' } },
                x: { grid: { display: false } }
            }
        }
    });
    
    // Grafico Mensal
    new Chart(document.getElementById('graficoMeses'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($meses); ?>,
            datasets: [{
                label: 'Horas',
                data: <?php echo json_encode($valoresMeses); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderColor: '#10b981',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10b981',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => v + 'h' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
