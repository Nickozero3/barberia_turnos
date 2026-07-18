<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pdo = db();
$editId = (int) ($_GET['edit'] ?? 0);
$editBarber = null;
$selectedServices = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE barbers SET active = IF(active = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Estado del peluquero actualizado.');
        redirect('/admin/peluqueros.php');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $color = trim((string) ($_POST['color'] ?? '#111827'));
    $days = array_map('intval', $_POST['work_days'] ?? []);
    $days = array_values(array_filter($days, fn (int $day): bool => $day >= 1 && $day <= 7));
    $workStart = trim((string) ($_POST['work_start'] ?? '09:00'));
    $workEnd = trim((string) ($_POST['work_end'] ?? '19:00'));
    $lunchStart = trim((string) ($_POST['lunch_start'] ?? ''));
    $lunchEnd = trim((string) ($_POST['lunch_end'] ?? ''));
    $serviceIds = array_map('intval', $_POST['services'] ?? []);

    if ($name === '' || !$days || !$serviceIds) {
        flash('danger', 'Completá el nombre, al menos un día y un servicio.');
        redirect('/admin/peluqueros.php' . ($id ? '?edit=' . $id : ''));
    }

    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE barbers SET name = ?, phone = ?, bio = ?, color = ?, work_days = ?, work_start = ?, work_end = ?, lunch_start = ?, lunch_end = ? WHERE id = ?'
            );
            $stmt->execute([$name, $phone ?: null, $bio ?: null, $color, implode(',', $days), $workStart, $workEnd, $lunchStart ?: null, $lunchEnd ?: null, $id]);
            $barberId = $id;
            $pdo->prepare('DELETE FROM barber_services WHERE barber_id = ?')->execute([$barberId]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO barbers (name, phone, bio, color, work_days, work_start, work_end, lunch_start, lunch_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $phone ?: null, $bio ?: null, $color, implode(',', $days), $workStart, $workEnd, $lunchStart ?: null, $lunchEnd ?: null]);
            $barberId = (int) $pdo->lastInsertId();
        }

        $link = $pdo->prepare('INSERT INTO barber_services (barber_id, service_id) VALUES (?, ?)');
        foreach ($serviceIds as $serviceId) {
            if ($serviceId > 0) $link->execute([$barberId, $serviceId]);
        }

        $pdo->commit();
        flash('success', $id > 0 ? 'Peluquero actualizado.' : 'Peluquero agregado.');
    } catch (Throwable $exception) {
        $pdo->rollBack();
        flash('danger', 'No se pudo guardar el peluquero.');
    }
    redirect('/admin/peluqueros.php');
}

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM barbers WHERE id = ?');
    $stmt->execute([$editId]);
    $editBarber = $stmt->fetch() ?: null;
    if ($editBarber) {
        $stmt = $pdo->prepare('SELECT service_id FROM barber_services WHERE barber_id = ?');
        $stmt->execute([$editId]);
        $selectedServices = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

$services = $pdo->query('SELECT * FROM services WHERE active = 1 ORDER BY name')->fetchAll();
$barbers = $pdo->query(
    'SELECT b.*, COUNT(bs.service_id) AS service_count
     FROM barbers b LEFT JOIN barber_services bs ON bs.barber_id = b.id
     GROUP BY b.id ORDER BY b.active DESC, b.name'
)->fetchAll();

$editDays = $editBarber ? array_map('intval', explode(',', $editBarber['work_days'])) : [1,2,3,4,5,6];
$pageTitle = 'Peluqueros';
$activePage = 'barbers';
require __DIR__ . '/_header.php';
?>
<div class="page-head"><div><h1>Peluqueros</h1><p>Horarios, descansos y servicios de cada profesional.</p></div></div>

<div class="grid cols-2" style="align-items:start">
    <section class="card form-card">
        <h3><?= $editBarber ? 'Editar peluquero' : 'Nuevo peluquero' ?></h3>
        <p style="color:var(--muted)">La disponibilidad pública se calcula con estos horarios.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) ($editBarber['id'] ?? 0) ?>">
            <div class="form-grid">
                <div class="field"><label>Nombre</label><input class="input" name="name" value="<?= e($editBarber['name'] ?? '') ?>" required></div>
                <div class="field"><label>Teléfono</label><input class="input" name="phone" value="<?= e($editBarber['phone'] ?? '') ?>"></div>
                <div class="field full"><label>Descripción</label><textarea name="bio"><?= e($editBarber['bio'] ?? '') ?></textarea></div>
                <div class="field"><label>Color en agenda</label><input class="input" type="color" name="color" value="<?= e($editBarber['color'] ?? '#111827') ?>"></div>
                <div class="field"><label>Inicio</label><input class="input" type="time" name="work_start" value="<?= e(substr($editBarber['work_start'] ?? '09:00', 0, 5)) ?>" required></div>
                <div class="field"><label>Fin</label><input class="input" type="time" name="work_end" value="<?= e(substr($editBarber['work_end'] ?? '19:00', 0, 5)) ?>" required></div>
                <div class="field"><label>Inicio descanso</label><input class="input" type="time" name="lunch_start" value="<?= e(substr($editBarber['lunch_start'] ?? '', 0, 5)) ?>"></div>
                <div class="field"><label>Fin descanso</label><input class="input" type="time" name="lunch_end" value="<?= e(substr($editBarber['lunch_end'] ?? '', 0, 5)) ?>"></div>
                <div class="field full">
                    <label>Días de trabajo</label>
                    <div class="checkbox-list">
                        <?php for ($day = 1; $day <= 7; $day++): ?>
                            <label class="check-chip"><input type="checkbox" name="work_days[]" value="<?= $day ?>" <?= in_array($day, $editDays, true) ? 'checked' : '' ?>> <?= e(day_name($day)) ?></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="field full">
                    <label>Servicios habilitados</label>
                    <div class="checkbox-list">
                        <?php foreach ($services as $service): ?>
                            <?php $checked = $editBarber ? in_array((int) $service['id'], $selectedServices, true) : true; ?>
                            <label class="check-chip"><input type="checkbox" name="services[]" value="<?= (int) $service['id'] ?>" <?= $checked ? 'checked' : '' ?>> <?= e($service['name']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="input-row" style="margin-top:18px">
                <button class="btn btn-primary" type="submit"><?= $editBarber ? 'Guardar cambios' : 'Agregar peluquero' ?></button>
                <?php if ($editBarber): ?><a class="btn btn-light" href="/admin/peluqueros.php">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="grid">
        <?php foreach ($barbers as $barber): ?>
            <article class="card pad">
                <div style="display:flex;justify-content:space-between;gap:14px;align-items:start">
                    <div style="display:flex;gap:12px;align-items:center">
                        <span class="icon-box" style="background:<?= e($barber['color']) ?>;color:#fff">✦</span>
                        <div><h3 style="margin:0"><?= e($barber['name']) ?></h3><small style="color:var(--muted)"><?= (int) $barber['service_count'] ?> servicios</small></div>
                    </div>
                    <span class="badge <?= $barber['active'] ? 'success' : 'muted' ?>"><?= $barber['active'] ? 'Activo' : 'Oculto' ?></span>
                </div>
                <p style="color:var(--muted)"><?= e($barber['bio']) ?></p>
                <div class="detail-row"><span>Horario</span><strong><?= substr($barber['work_start'],0,5) ?>–<?= substr($barber['work_end'],0,5) ?></strong></div>
                <div class="input-row" style="margin-top:16px">
                    <a class="btn btn-light btn-sm" href="?edit=<?= (int) $barber['id'] ?>">Editar</a>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $barber['id'] ?>"><button class="btn btn-sm <?= $barber['active'] ? 'btn-danger' : 'btn-success' ?>" type="submit"><?= $barber['active'] ? 'Ocultar' : 'Activar' ?></button></form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
