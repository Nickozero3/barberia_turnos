<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$search = trim((string) ($_GET['q'] ?? ''));
$sql = "SELECT c.*, COUNT(a.id) AS appointment_count, MAX(a.appointment_date) AS last_visit
        FROM customers c LEFT JOIN appointments a ON a.customer_id = c.id";
$params = [];
if ($search !== '') {
    $sql .= ' WHERE c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?';
    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}
$sql .= ' GROUP BY c.id ORDER BY c.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Clientes';
$activePage = 'customers';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div><h1>Clientes</h1><p>Se crean automáticamente con la primera reserva.</p></div>
</div>
<form class="card pad" method="get" style="margin-bottom:18px">
    <div class="input-row">
        <input class="input" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre, teléfono o correo">
        <button class="btn btn-primary" type="submit">Buscar</button>
        <?php if ($search): ?><a class="btn btn-light" href="/admin/clientes.php">Limpiar</a><?php endif; ?>
    </div>
</form>
<section class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Cliente</th><th>Contacto</th><th>Turnos</th><th>Última fecha</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><strong><?= e($customer['name']) ?></strong><br><small style="color:var(--muted)">Alta: <?= date('d/m/Y', strtotime($customer['created_at'])) ?></small></td>
                    <td><?= e($customer['phone']) ?><br><small style="color:var(--muted)"><?= e($customer['email']) ?></small></td>
                    <td><?= (int) $customer['appointment_count'] ?></td>
                    <td><?= $customer['last_visit'] ? date('d/m/Y', strtotime($customer['last_visit'])) : '—' ?></td>
                    <td><a class="btn btn-light btn-sm" target="_blank" href="<?= e(phone_whatsapp_url($customer['phone'], 'Hola ' . $customer['name'] . ', te escribimos desde ' . setting('business_name', 'la barbería'))) ?>">WhatsApp ↗</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$customers): ?><tr><td colspan="5"><div class="empty-box">No se encontraron clientes.</div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
