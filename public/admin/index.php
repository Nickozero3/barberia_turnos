<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pdo = db();
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status NOT IN ('cancelled', 'no_show')");
$stmt->execute([$today]);
$todayCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(price_at_booking), 0) FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status = 'completed'");
$stmt->execute([$monthStart, $monthEnd]);
$monthRevenue = (int) $stmt->fetchColumn();

$customerCount = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$lowStockCount = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1 AND stock <= min_stock')->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT a.*, c.name AS customer_name, s.name AS service_name, b.name AS barber_name, b.color AS barber_color
     FROM appointments a
     INNER JOIN customers c ON c.id = a.customer_id
     INNER JOIN services s ON s.id = a.service_id
     INNER JOIN barbers b ON b.id = a.barber_id
     WHERE a.appointment_date = ?
     ORDER BY a.start_time LIMIT 8"
);
$stmt->execute([$today]);
$todayAppointments = $stmt->fetchAll();

$pageTitle = 'Resumen';
$activePage = 'dashboard';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div>
        <h1>Hola, <?= e(admin_user()['name']) ?></h1>
        <p>Así viene la barbería hoy, <?= date('d/m/Y') ?>.</p>
    </div>
    <a class="btn btn-primary" href="/reservar.php" target="_blank">+ Crear turno</a>
</div>

<div class="grid cols-4 stats" style="margin-bottom:22px">
    <article class="card stat-card"><div class="label">Turnos de hoy</div><div class="value"><?= $todayCount ?></div><div class="hint">Activos en la agenda</div></article>
    <article class="card stat-card"><div class="label">Facturación mensual</div><div class="value"><?= money($monthRevenue) ?></div><div class="hint">Solo turnos finalizados</div></article>
    <article class="card stat-card"><div class="label">Clientes</div><div class="value"><?= $customerCount ?></div><div class="hint">Registrados automáticamente</div></article>
    <article class="card stat-card"><div class="label">Stock bajo</div><div class="value"><?= $lowStockCount ?></div><div class="hint">Productos para reponer</div></article>
</div>

<section class="card">
    <div class="page-head" style="padding:20px 20px 0;margin-bottom:10px">
        <div><h1 style="font-size:21px">Próximos turnos de hoy</h1><p>Acceso rápido al detalle de cada reserva.</p></div>
        <a class="btn btn-light btn-sm" href="/admin/agenda.php">Ver agenda</a>
    </div>
    <?php if (!$todayAppointments): ?>
        <div class="empty-box" style="margin:20px">Todavía no hay turnos para hoy.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Hora</th><th>Cliente</th><th>Servicio</th><th>Profesional</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($todayAppointments as $appointment): ?>
                    <tr>
                        <td><strong><?= substr($appointment['start_time'], 0, 5) ?></strong></td>
                        <td><?= e($appointment['customer_name']) ?></td>
                        <td><?= e($appointment['service_name']) ?></td>
                        <td><span class="color-dot" style="display:inline-block;background:<?= e($appointment['barber_color']) ?>"></span> <?= e($appointment['barber_name']) ?></td>
                        <td><span class="badge <?= e(status_class($appointment['status'])) ?>"><?= e(status_label($appointment['status'])) ?></span></td>
                        <td><a class="btn btn-light btn-sm" href="/admin/turno.php?id=<?= (int) $appointment['id'] ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
