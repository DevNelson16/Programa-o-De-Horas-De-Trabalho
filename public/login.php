<?php

/**
 * ============================================================
 * Página de Login
 * Corrigida + Completa
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Se já estiver autenticado
 */
if (estaAutenticado()) {
    redirecionar('index.php');
}

$erro = '';
$flash = getFlash();

/**
 * Processar Login
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {

        $erro = 'Preencha todos os campos.';
    } else {

        $resultado = fazerLogin($email, $password);

        if (!empty($resultado['sucesso']) && $resultado['sucesso'] === true) {

            setFlash('success', $resultado['mensagem'] ?? 'Login efetuado com sucesso.');

            redirecionar('index.php');
            exit;
        } else {

            $erro = $resultado['mensagem'] ?? 'Email ou password inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo APP_NAME; ?> - Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo APP_VERSION; ?>">

</head>

<body class="login-page">

    <div class="login-container">

        <div class="login-card">

            <div class="login-logo">
                <i class="fas fa-code"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Controlo de Horas de Programação</p>
            </div>

            <?php if ($flash): ?>
                <div class="flash-message flash-<?php echo $flash['tipo']; ?>" style="position:static;margin-bottom:20px;max-width:100%;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($flash['mensagem']); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="flash-message flash-error" style="position:static;margin-bottom:20px;max-width:100%;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($erro); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder="seu@email.com"
                            required
                            autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-lock"></i>
                        <input
                            type="password"
                            name="password"
                            class="form-input"
                            placeholder="Sua password"
                            required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:10px;">
                    <i class="fas fa-arrow-right"></i>
                    Entrar
                </button>

            </form>

            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #eee;text-align:center;">

                <p style="font-size:14px;color:#666;">
                    <i class="fas fa-info-circle"></i>
                    Contas Demo
                </p>

                <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px;font-size:13px;">

                    <div style="background:#f5f5f5;padding:10px;border-radius:8px;">
                        <strong>Admin:</strong>
                        admin@sistema.pt / admin123
                    </div>

                    <div style="background:#f5f5f5;padding:10px;border-radius:8px;">
                        <strong>User:</strong>
                        user@sistema.pt / user123
                    </div>

                </div>

            </div>

        </div>

        <p style="text-align:center;margin-top:24px;font-size:14px;color:#ffffff90;">
            <i class="fas fa-shield-halved"></i>
            Sistema seguro com Prepared Statements
        </p>

    </div>

</body>

</html>