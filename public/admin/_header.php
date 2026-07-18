<?php
$user = admin_user();
$flashMessage = get_flash();
$businessName = setting('business_name', 'fioreee_barber');
$current = $activePage ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111827">
    <title><?= e($pageTitle ?? 'Administración') ?> · <?= e($businessName) ?></title>
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
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="brand" href="/admin/index.php">
            <span class="brand-mark">✂</span>
            <span><?= e($businessName) ?></span>
        </a>
        <nav class="nav">
            <a class="<?= $current === 'dashboard' ? 'active' : '' ?>" href="/admin/index.php"><span>⌂</span> Resumen</a>
            <a class="<?= $current === 'agenda' ? 'active' : '' ?>" href="/admin/agenda.php"><span>▦</span> Agenda</a>
            <a class="<?= $current === 'services' ? 'active' : '' ?>" href="/admin/servicios.php"><span>✂</span> Servicios</a>
            <a class="<?= $current === 'barbers' ? 'active' : '' ?>" href="/admin/peluqueros.php"><span>♟</span> Peluqueros</a>
            <a class="<?= $current === 'products' ? 'active' : '' ?>" href="/admin/productos.php"><span>□</span> Productos</a>
            <a class="<?= $current === 'customers' ? 'active' : '' ?>" href="/admin/clientes.php"><span>◎</span> Clientes</a>
            <a class="<?= $current === 'settings' ? 'active' : '' ?>" href="/admin/configuracion.php"><span>⚙</span> Configuración</a>
        </nav>
        <div class="sidebar-footer">
            <div><?= e($user['name'] ?? '') ?></div>
            <a href="/admin/logout.php">Cerrar sesión</a>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="btn btn-light mobile-nav-toggle" type="button" data-sidebar-toggle>☰ Menú</button>
            <strong><?= e($pageTitle ?? 'Administración') ?></strong>
            <a class="btn btn-light btn-sm" href="/" target="_blank">Ver reservas ↗</a>
        </header>
        <div class="admin-content">
            <?php if ($flashMessage): ?>
                <div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
            <?php endif; ?>
