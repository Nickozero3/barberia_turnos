<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $allowed = ['business_name', 'business_subtitle', 'business_phone', 'business_address', 'booking_notice'];
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($allowed as $key) {
        $stmt->execute([$key, trim((string) ($_POST[$key] ?? ''))]);
    }
    flash('success', 'Configuración guardada.');
    redirect('/admin/configuracion.php');
}

$pageTitle = 'Configuración';
$activePage = 'settings';
require __DIR__ . '/_header.php';
?>
<div class="page-head"><div><h1>Configuración</h1><p>Información visible en la reserva pública.</p></div></div>
<section class="card form-card" style="max-width:760px">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field full"><label>Nombre de la barbería</label><input class="input" name="business_name" value="<?= e(setting('business_name')) ?>" required></div>
            <div class="field full"><label>Frase principal</label><input class="input" name="business_subtitle" value="<?= e(setting('business_subtitle')) ?>"></div>
            <div class="field"><label>Teléfono</label><input class="input" name="business_phone" value="<?= e(setting('business_phone')) ?>"></div>
            <div class="field"><label>Dirección</label><input class="input" name="business_address" value="<?= e(setting('business_address')) ?>"></div>
            <div class="field full"><label>Aviso al reservar</label><textarea name="booking_notice"><?= e(setting('booking_notice')) ?></textarea></div>
        </div>
        <button class="btn btn-primary" style="margin-top:18px" type="submit">Guardar configuración</button>
    </form>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
