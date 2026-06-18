<?php

/**
 * ============================================================
 * public/index.php
 * Dashboard Principal
 * ============================================================
 */

require_once __DIR__ . '/../includes/header.php';

$user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header("Location: login.php");
    exit;
}

$stats = getEstatisticasHoras($user_id);

/**
 * Últimos 7 dias
 */
$dias = [];
$valores = [];

for ($i = 6; $i >= 0; $i--) {

    $data = date('Y-m-d', strtotime("-{$i} days"));

    $dias[] = date('d/m', strtotime($data));

    $result = fetchOne(
        "SELECT COALESCE(SUM(total_horas),0) as total 
         FROM horas 
         WHERE user_id = ? 
         AND data = ? 
         AND status = 'concluido'",
        [$user_id, $data]
    );

    $valores[] = (float)$result['total'];
}

/**
 * Últimos registos
 */
$ultimosRegistos = fetchAll(
    "SELECT * FROM horas 
     WHERE user_id = ? 
     AND status = 'concluido'
     ORDER BY data DESC, hora_fim DESC
     LIMIT 5",
    [$user_id]
);

/**
 * Últimos relatórios
 */
$ultimosRelatorios = fetchAll(
    "SELECT r.*, u.nome as autor
     FROM relatorios r
     JOIN utilizadores u ON u.id = r.user_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC
     LIMIT 3",
    [$user_id]
);

$titulo = "Dashboard";
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_nome']); ?></p>
</div>

<div class="stats-grid">

    <div class="stat-card">
        <h3><?php echo formatarHoras($stats['hoje']); ?></h3>
        <p>Hoje</p>
    </div>

    <div class="stat-card">
        <h3><?php echo formatarHoras($stats['semana']); ?></h3>
        <p>Semana</p>
    </div>

    <div class="stat-card">
        <h3><?php echo formatarHoras($stats['mes']); ?></h3>
        <p>Mês</p>
    </div>

    <div class="stat-card">
        <h3><?php echo formatarHoras($stats['media']); ?></h3>
        <p>Média</p>
    </div>

</div>

<div class="card">
    <h2>Horas últimos 7 dias</h2>
    <canvas id="horasChart"></canvas>
</div>

<div class="card">
    <h2>Últimos Registos</h2>

    <?php if (empty($ultimosRegistos)): ?>
        <p>Sem registos.</p>
    <?php else: ?>

        <table>
            <tr>
                <th>Data</th>
                <th>Inicio</th>
                <th>Fim</th>
                <th>Total</th>
            </tr>

            <?php foreach ($ultimosRegistos as $r): ?>

                <tr>
                    <td><?php echo date('d/m/Y', strtotime($r['data'])); ?></td>
                    <td><?php echo substr($r['hora_inicio'], 0, 5); ?></td>
                    <td><?php echo substr($r['hora_fim'], 0, 5); ?></td>
                    <td><?php echo formatarHoras($r['total_horas']); ?></td>
                </tr>

            <?php endforeach; ?>

        </table>

    <?php endif; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const ctx = document.getElementById("horasChart");

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: <?php echo json_encode($dias); ?>,
                datasets: [{
                    label: "Horas",
                    data: <?php echo json_encode($valores); ?>,
                    borderWidth: 1
                }]
            }
        });

    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>