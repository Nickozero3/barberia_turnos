<?php
$businessName = setting('business_name', 'fioreee_barber');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111827">
    <title><?= e($pageTitle ?? $businessName) ?></title>
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
<header class="site-header">
    <div class="container inner">
        <a class="brand" href="/">
            <span class="brand-mark">✂</span>
            <span><?= e($businessName) ?></span>
        </a>
        <div class="header-actions">
            <a class="btn btn-light hide-mobile" href="/admin/login.php">Administración</a>
            <a class="btn btn-primary" href="/reservar.php">Reservar turno</a>
        </div>
    </div>
</header>
