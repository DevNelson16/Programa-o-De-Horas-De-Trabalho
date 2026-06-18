<?php
/**
 * ============================================================
 * Logout - Terminar Sessao
 * ============================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

fazerLogout();

// Redirecionar para o login
header("Location: " . APP_URL . "/public/login.php");
exit;
