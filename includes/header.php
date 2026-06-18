<?php

/**
 * ============================================================
 * Header Comum - Incluido em todas as paginas
 * ============================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

/**
 * 🔥 IMPORTANTE: Buffer de saída para evitar erros de headers
 */
if (!headers_sent()) {
    ob_start();
}

$paginasProtegidas = ['timer', 'historico', 'relatorios', 'perfil'];
$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');

if (in_array($paginaAtual, $paginasProtegidas)) {
    protegerPagina();
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo APP_NAME; ?> - <?php echo $titulo ?? 'Dashboard'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/style.css?v=<?php echo APP_VERSION; ?>">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>

<body>

    <?php if ($flash): ?>
        <div class="flash-message flash-<?php echo $flash['tipo']; ?>" id="flashMessage">
            <i class="fas fa-<?php echo $flash['tipo'] === 'success' ? 'check-circle' : ($flash['tipo'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($flash['mensagem']); ?></span>
            <button onclick="document.getElementById('flashMessage').remove()" class="flash-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if (estaAutenticado()): ?>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-code"></i>
                    <span><?php echo APP_NAME; ?></span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-label">Principal</span>

                    <a href="<?php echo APP_URL; ?>/public/index.php"
                        class="nav-link <?php echo $paginaAtual === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="<?php echo APP_URL; ?>/public/timer.php"
                        class="nav-link <?php echo $paginaAtual === 'timer' ? 'active' : ''; ?>">
                        <i class="fas fa-stopwatch"></i>
                        <span>Temporizador</span>
                    </a>

                    <a href="<?php echo APP_URL; ?>/public/historico.php"
                        class="nav-link <?php echo $paginaAtual === 'historico' ? 'active' : ''; ?>">
                        <i class="fas fa-clock-rotate-left"></i>
                        <span>Histórico</span>
                    </a>

                    <a href="<?php echo APP_URL; ?>/public/relatorios.php"
                        class="nav-link <?php echo $paginaAtual === 'relatorios' ? 'active' : ''; ?>">
                        <i class="fas fa-file-lines"></i>
                        <span>Relatórios</span>
                    </a>
                </div>

                <?php if (eAdmin()): ?>
                    <div class="nav-section">
                        <span class="nav-label">Administração</span>

                        <a href="<?php echo APP_URL; ?>/public/admin/dashboard.php"
                            class="nav-link">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard Admin</span>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="nav-section">
                    <span class="nav-label">Conta</span>

                    <a href="<?php echo APP_URL; ?>/public/perfil.php"
                        class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>Perfil</span>
                    </a>

                    <a href="<?php echo APP_URL; ?>/public/logout.php"
                        class="nav-link nav-logout">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Sair</span>
                    </a>
                </div>
            </nav>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content">

            <header class="topbar">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="topbar-info">
                    <span class="data-atual"><?php echo formatarData(date('Y-m-d')); ?></span>
                </div>

                <div class="topbar-user">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_nome']); ?></span>
                        <span class="user-role"><?php echo eAdmin() ? 'Administrador' : 'Programador'; ?></span>
                    </div>

                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </header>

            <div class="page-content">

            <?php endif; ?>