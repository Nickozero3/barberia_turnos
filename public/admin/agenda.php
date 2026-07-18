<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$barbers = db()->query('SELECT * FROM barbers WHERE active = 1 ORDER BY name')->fetchAll();
$stmt = db()->prepare(
    "SELECT a.*, c.name AS customer_name, c.phone, s.name AS service_name
     FROM appointments a
     INNER JOIN customers c ON c.id = a.customer_id
     INNER JOIN services s ON s.id = a.service_id
     WHERE a.appointment_date = ?
     ORDER BY a.start_time"
);
$stmt->execute([$date]);
$appointments = $stmt->fetchAll();
$byBarber = [];
foreach ($appointments as $appointment) {
    $byBarber[(int) $appointment['barber_id']][] = $appointment;
}

$pageTitle = 'Agenda';
$activePage = 'agenda';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div><h1>Agenda</h1><p>Turnos separados por profesional.</p></div>
    <a class="btn btn-primary" href="/reservar.php" target="_blank">+ Nuevo turno</a>
</div>

<div class="agenda-toolbar card pad">
    <div class="field" style="min-width:230px">
        <label for="agenda-date">Fecha</label>
        <input class="input" id="agenda-date" type="date" value="<?= e($date) ?>" onchange="location.href='/admin/agenda.php?date='+this.value">
    </div>
    <div>
        <a class="btn btn-light btn-sm" href="?date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>">← Anterior</a>
        <a class="btn btn-light btn-sm" href="?date=<?= date('Y-m-d') ?>">Hoy</a>
        <a class="btn btn-light btn-sm" href="?date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>">Siguiente →</a>
    </div>
</div>

<div class="agenda-columns">
    <?php foreach ($barbers as $barber): ?>
        <section class="barber-column">
            <header class="barber-column-head">
                <span class="color-dot" style="background:<?= e($barber['color']) ?>"></span>
                <div><strong><?= e($barber['name']) ?></strong><div style="color:var(--muted);font-size:12px"><?= substr($barber['work_start'],0,5) ?> a <?= substr($barber['work_end'],0,5) ?></div></div>
            </header>
            <div class="appointment-list">
                <?php if (empty($byBarber[(int) $barber['id']])): ?>
                    <div class="empty-box">Sin turnos.</div>
                <?php else: ?>
                    <?php foreach ($byBarber[(int) $barber['id']] as $appointment): ?>
                        <a class="appointment-item" style="border-left-color:<?= e($barber['color']) ?>" href="/admin/turno.php?id=<?= (int) $appointment['id'] ?>">
                            <div class="appointment-time"><?= substr($appointment['start_time'],0,5) ?>–<?= substr($appointment['end_time'],0,5) ?></div>
                            <h4><?= e($appointment['customer_name']) ?></h4>
                            <p><?= e($appointment['service_name']) ?> · <?= money($appointment['price_at_booking']) ?></p>
                            <div class="appointment-foot">
                                <span class="badge <?= e(status_class($appointment['status'])) ?>"><?= e(status_label($appointment['status'])) ?></span>
                                <span style="font-size:12px;color:var(--muted)"><?= e($appointment['phone']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
