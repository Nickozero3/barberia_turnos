<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pdo = db();
$editId = (int) ($_GET['edit'] ?? 0);
$editService = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE services SET active = IF(active = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Estado del servicio actualizado.');
        redirect('/admin/servicios.php');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $price = max(0, (int) ($_POST['price'] ?? 0));
    $duration = max(5, (int) ($_POST['duration_minutes'] ?? 30));

    if ($name === '') {
        flash('danger', 'El nombre del servicio es obligatorio.');
        redirect('/admin/servicios.php' . ($id ? '?edit=' . $id : ''));
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE services SET name = ?, description = ?, price = ?, duration_minutes = ? WHERE id = ?');
        $stmt->execute([$name, $description ?: null, $price, $duration, $id]);
        flash('success', 'Servicio actualizado.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO services (name, description, price, duration_minutes) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $description ?: null, $price, $duration]);
        $newId = (int) $pdo->lastInsertId();
        $barberIds = $pdo->query('SELECT id FROM barbers WHERE active = 1')->fetchAll(PDO::FETCH_COLUMN);
        $link = $pdo->prepare('INSERT IGNORE INTO barber_services (barber_id, service_id) VALUES (?, ?)');
        foreach ($barberIds as $barberId) $link->execute([(int) $barberId, $newId]);
        flash('success', 'Servicio agregado y habilitado para los peluqueros activos.');
    }

    redirect('/admin/servicios.php');
}

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$editId]);
    $editService = $stmt->fetch() ?: null;
}

$services = $pdo->query(
    'SELECT s.*, COUNT(bs.barber_id) AS barber_count
     FROM services s LEFT JOIN barber_services bs ON bs.service_id = s.id
     GROUP BY s.id ORDER BY s.active DESC, s.name'
)->fetchAll();

$pageTitle = 'Servicios';
$activePage = 'services';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div><h1>Servicios</h1><p>Configurá precios y duración de los turnos.</p></div>
</div>

<div class="grid cols-2" style="align-items:start">
    <section class="card form-card">
        <div class="form-section" style="padding-top:0;margin-top:0;border:0">
            <h3><?= $editService ? 'Editar servicio' : 'Nuevo servicio' ?></h3>
            <p>Los precios se guardan como números enteros.</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) ($editService['id'] ?? 0) ?>">
                <div class="field" style="margin-bottom:14px">
                    <label>Nombre</label>
                    <input class="input" name="name" value="<?= e($editService['name'] ?? '') ?>" placeholder="Ejemplo: Corte infantil" required>
                </div>
                <div class="field" style="margin-bottom:14px">
                    <label>Descripción</label>
                    <textarea name="description" placeholder="Descripción corta para el cliente"><?= e($editService['description'] ?? '') ?></textarea>
                </div>
                <div class="form-grid">
                    <div class="field"><label>Precio</label><input class="input" name="price" type="number" min="0" step="1" value="<?= (int) ($editService['price'] ?? 12000) ?>" required></div>
                    <div class="field"><label>Duración (minutos)</label><input class="input" name="duration_minutes" type="number" min="5" step="5" value="<?= (int) ($editService['duration_minutes'] ?? 30) ?>" required></div>
                </div>
                <div class="input-row" style="margin-top:18px">
                    <button class="btn btn-primary" type="submit"><?= $editService ? 'Guardar cambios' : 'Agregar servicio' ?></button>
                    <?php if ($editService): ?><a class="btn btn-light" href="/admin/servicios.php">Cancelar</a><?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Servicio</th><th>Precio</th><th>Duración</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><strong><?= e($service['name']) ?></strong><br><small style="color:var(--muted)"><?= (int) $service['barber_count'] ?> profesionales</small></td>
                        <td><?= money($service['price']) ?></td>
                        <td><?= (int) $service['duration_minutes'] ?> min</td>
                        <td><span class="badge <?= $service['active'] ? 'success' : 'muted' ?>"><?= $service['active'] ? 'Activo' : 'Oculto' ?></span></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-light btn-sm" href="?edit=<?= (int) $service['id'] ?>">Editar</a>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                                    <button class="btn btn-sm <?= $service['active'] ? 'btn-danger' : 'btn-success' ?>" type="submit"><?= $service['active'] ? 'Ocultar' : 'Activar' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
