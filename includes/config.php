<?php
/**
 * ============================================================
 * Sistema de Controlo de Horas de Programacao
 * Ficheiro de Configuracao
 * ============================================================
 */

// Prevenir acesso direto
if (!defined('PROTECAO')) {
    define('PROTECAO', true);
}

// Configuracoes da base de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'programacao_horas');
define('DB_USER', 'root');
define('DB_PASS', '');         // XAMPP padrao: sem password
// define('DB_PASS', 'root');  // MAMP: password = root

// Configuracoes da aplicacao
define('APP_NAME', 'ProgHours');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/programacao-horas'); // Ajustar conforme necessario

// Zona horaria
date_default_timezone_set('Europe/Lisbon');

// Iniciar sessao (com configuracoes seguras)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Mostrar erros em desenvolvimento (desativar em producao)
error_reporting(E_ALL);
ini_set('display_errors', 1);
