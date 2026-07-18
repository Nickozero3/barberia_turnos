<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

if (admin_user()) {
    redirect('/admin/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = mb_strtolower(trim((string) ($_POST['username'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        redirect('/admin/index.php');
    }

    $error = 'El usuario o la contraseña no son correctos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111827">
    <title>Ingresar · <?= e(setting('business_name', 'fioreee_barber')) ?></title>
    <script>
        (() => {
            try {
                const savedTheme = localStorage.getItem('fioreee-theme');
                if (savedTheme === 'dark') {
                    document.documentElement.dataset.theme = 'dark';
                }
            } catch (error) {
                // El tema claro sigue funcionando aunque el navegador bloquee el almacenamiento.
            }
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/theme.js" defer></script>
</head>
<body class="login-page">
    <form class="login-box" method="post">
        <?= csrf_field() ?>
        <a class="brand" href="/"><span class="brand-mark">✂</span><span><?= e(setting('business_name', 'fioreee_barber')) ?></span></a>
        <h1>Panel administrativo</h1>
        <p>Gestioná turnos, profesionales, servicios y productos.</p>
        <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
        <div class="field" style="margin-bottom:14px">
            <label for="username">Usuario</label>
            <input class="input" id="username" name="username" type="text" value="<?= e($_POST['username'] ?? 'fiorebaber') ?>" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary btn-block" style="margin-top:20px" type="submit">Ingresar</button>
        <div class="credentials">Acceso: <strong>fiorebaber</strong> / <strong>admin2027</strong></div>
    </form>
</body>
</html>
