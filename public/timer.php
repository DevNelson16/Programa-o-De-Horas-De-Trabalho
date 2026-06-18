<?php
/**
 * ============================================================
 * Pagina do Temporizador
 * Iniciar, Pausar, Parar e Guardar
 * ============================================================
 */

require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$titulo = 'Temporizador';
$extraJS = 'timer.js';

// Estatisticas rapidas
$stats = getEstatisticasHoras($user_id);
?>

<div class="page-header">
    <h1><i class="fas fa-stopwatch" style="margin-right:12px;color:var(--primary);"></i>Temporizador</h1>
    <p>Controla o teu tempo de programacao. Inicia, pausa e guarda automaticamente.</p>
</div>

<!-- Stats Rapidas -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($stats['hoje']); ?></h3><p>Hoje</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($stats['semana']); ?></h3><p>Semana</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($stats['mes']); ?></h3><p>Mes</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info"><h3><?php echo formatarHoras($stats['media']); ?></h3><p>Media</p></div>
    </div>
</div>

<!-- Temporizador Principal -->
<div class="card" style="margin-top:24px;">
    <div class="card-body">
        <div class="timer-container">
            <!-- Status -->
            <div class="timer-status idle" id="timerStatus">
                <i class="fas fa-clock" style="margin-right:6px;"></i> Pronto para comecar
            </div>
            
            <!-- Display -->
            <div class="timer-display" id="timerDisplay">00:00:00</div>
            
            <!-- Controles -->
            <div class="timer-controls">
                <button class="timer-btn timer-btn-start" id="btnIniciar">
                    <i class="fas fa-play"></i> Iniciar
                </button>
                <button class="timer-btn timer-btn-pause" id="btnPausar" disabled>
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="timer-btn timer-btn-stop" id="btnParar" disabled>
                    <i class="fas fa-stop"></i> Terminar
                </button>
            </div>
            
            <!-- Descricao -->
            <div class="timer-description">
                <label class="form-label" for="timerDescricao">Descricao (opcional)</label>
                <textarea id="timerDescricao" class="form-input" rows="3" placeholder="O que vais programar/estudar? Ex: PHP, JavaScript, CSS..."></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Dica -->
<div style="margin-top:24px; text-align:center; color:var(--text-secondary); font-size:var(--font-size-sm);">
    <i class="fas fa-lightbulb" style="color:var(--warning);margin-right:6px;"></i>
    Dica: O temporizador continua a contar mesmo que recarregues a pagina.
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
