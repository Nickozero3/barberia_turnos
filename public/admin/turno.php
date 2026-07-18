<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit('Turno no encontrado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'update');

    if ($action === 'update') {
        $allowedStatuses = ['pending', 'confirmed', 'waiting', 'in_progress', 'completed', 'cancelled', 'no_show'];
        $allowedMethods = ['', 'cash', 'transfer', 'card', 'mercadopago'];
        $status = (string) ($_POST['status'] ?? 'confirmed');
        $method = (string) ($_POST['payment_method'] ?? '');
        $paid = isset($_POST['paid']) ? 1 : 0;
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if (!in_array($status, $allowedStatuses, true)) $status = 'confirmed';
        if (!in_array($method, $allowedMethods, true)) $method = '';

        $stmt = $pdo->prepare('UPDATE appointments SET status = ?, payment_method = ?, paid = ?, notes = ? WHERE id = ?');
        $stmt->execute([$status, $method ?: null, $paid, $notes ?: null, $id]);
        flash('success', 'Turno actualizado.');
    }

    if ($action === 'add_product') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND active = 1 FOR UPDATE');
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if (!$product || (int) $product['stock'] < $quantity) {
                throw new RuntimeException('No hay stock suficiente para agregar ese producto.');
            }

            $existing = $pdo->prepare('SELECT id FROM appointment_products WHERE appointment_id = ? AND product_id = ?');
            $existing->execute([$id, $productId]);
            $existingId = (int) ($existing->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $stmt = $pdo->prepare('UPDATE appointment_products SET quantity = quantity + ? WHERE id = ?');
                $stmt->execute([$quantity, $existingId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO appointment_products (appointment_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
                $stmt->execute([$id, $productId, $quantity, (int) $product['price']]);
            }

            $stmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            $stmt->execute([$quantity, $productId]);
            $pdo->commit();
            flash('success', 'Producto agregado al turno.');
        } catch (Throwable $exception) {
            $pdo->rollBack();
            flash('danger', $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo agregar el producto.');
        }
    }

    if ($action === 'remove_product') {
        $lineId = (int) ($_POST['line_id'] ?? 0);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM appointment_products WHERE id = ? AND appointment_id = ? FOR UPDATE');
            $stmt->execute([$lineId, $id]);
            $line = $stmt->fetch();
            if (!$line) throw new RuntimeException('Producto no encontrado en el turno.');

            $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([(int) $line['quantity'], (int) $line['product_id']]);
            $pdo->prepare('DELETE FROM appointment_products WHERE id = ?')->execute([$lineId]);
            $pdo->commit();
            flash('success', 'Producto quitado y stock devuelto.');
        } catch (Throwable $exception) {
            $pdo->rollBack();
            flash('danger', 'No se pudo quitar el producto.');
        }
    }

    redirect('/admin/turno.php?id=' . $id);
}

$stmt = $pdo->prepare(
    'SELECT a.*, c.name AS customer_name, c.phone, c.email, s.name AS service_name, s.duration_minutes,
            b.name AS barber_name, b.color AS barber_color
     FROM appointments a
     INNER JOIN customers c ON c.id = a.customer_id
     INNER JOIN services s ON s.id = a.service_id
     INNER JOIN barbers b ON b.id = a.barber_id
     WHERE a.id = ?'
);
$stmt->execute([$id]);
$appointment = $stmt->fetch();
if (!$appointment) {
    http_response_code(404);
    exit('Turno no encontrado.');
}

$stmt = $pdo->prepare(
    'SELECT ap.*, p.name AS product_name FROM appointment_products ap
     INNER JOIN products p ON p.id = ap.product_id WHERE ap.appointment_id = ? ORDER BY ap.id'
);
$stmt->execute([$id]);
$lines = $stmt->fetchAll();
$productTotal = 0;
foreach ($lines as $line) $productTotal += (int) $line['quantity'] * (int) $line['unit_price'];
$total = (int) $appointment['price_at_booking'] + $productTotal;
$products = $pdo->query('SELECT * FROM products WHERE active = 1 AND stock > 0 ORDER BY name')->fetchAll();

$paymentLabels = ['' => 'Sin definir', 'cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'mercadopago' => 'Mercado Pago'];
$pageTitle = 'Detalle del turno';
$activePage = 'agenda';
require __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div><h1>Turno #<?= (int) $appointment['id'] ?></h1><p><?= date('d/m/Y', strtotime($appointment['appointment_date'])) ?> · <?= substr($appointment['start_time'],0,5) ?> · <?= e($appointment['barber_name']) ?></p></div>
    <a class="btn btn-light" href="/admin/agenda.php?date=<?= e($appointment['appointment_date']) ?>">← Volver a agenda</a>
</div>

<div class="grid cols-2" style="align-items:start">
    <div class="grid">
        <section class="card pad">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:start">
                <div><h2 style="margin:0 0 4px"><?= e($appointment['customer_name']) ?></h2><p style="margin:0;color:var(--muted)"><?= e($appointment['phone']) ?><?= $appointment['email'] ? ' · ' . e($appointment['email']) : '' ?></p></div>
                <span class="badge <?= e(status_class($appointment['status'])) ?>"><?= e(status_label($appointment['status'])) ?></span>
            </div>
            <div class="detail-list" style="margin-top:18px">
                <div class="detail-row"><span>Servicio</span><strong><?= e($appointment['service_name']) ?></strong></div>
                <div class="detail-row"><span>Profesional</span><strong><span class="color-dot" style="display:inline-block;background:<?= e($appointment['barber_color']) ?>"></span> <?= e($appointment['barber_name']) ?></strong></div>
                <div class="detail-row"><span>Horario</span><strong><?= substr($appointment['start_time'],0,5) ?>–<?= substr($appointment['end_time'],0,5) ?></strong></div>
                <div class="detail-row"><span>Precio del servicio</span><strong><?= money($appointment['price_at_booking']) ?></strong></div>
            </div>
            <div class="input-row" style="margin-top:18px">
                <a class="btn btn-success btn-sm" target="_blank" href="<?= e(phone_whatsapp_url($appointment['phone'], 'Hola ' . $appointment['customer_name'] . ', te escribimos desde ' . setting('business_name', 'la barbería') . ' por tu turno del ' . date('d/m/Y', strtotime($appointment['appointment_date'])) . ' a las ' . substr($appointment['start_time'],0,5))) ?>">WhatsApp ↗</a>
                <a class="btn btn-light btn-sm" target="_blank" href="/confirmacion.php?token=<?= e($appointment['public_token']) ?>">Enlace del cliente ↗</a>
            </div>
        </section>

        <section class="card form-card">
            <h3>Estado y pago</h3>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="update">
                <div class="form-grid">
                    <div class="field"><label>Estado</label><select name="status">
                        <?php foreach (['pending','confirmed','waiting','in_progress','completed','cancelled','no_show'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= $appointment['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="field"><label>Método de pago</label><select name="payment_method">
                        <?php foreach ($paymentLabels as $key => $label): ?><option value="<?= e($key) ?>" <?= ($appointment['payment_method'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="field full"><label class="check-chip"><input type="checkbox" name="paid" value="1" <?= $appointment['paid'] ? 'checked' : '' ?>> Pago registrado</label></div>
                    <div class="field full"><label>Notas internas</label><textarea name="notes"><?= e($appointment['notes']) ?></textarea></div>
                </div>
                <button class="btn btn-primary" style="margin-top:16px" type="submit">Guardar cambios</button>
            </form>
        </section>
    </div>

    <div class="grid">
        <section class="card form-card">
            <h3>Agregar producto</h3>
            <p style="color:var(--muted)">Se descuenta automáticamente del stock.</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="add_product">
                <div class="form-grid">
                    <div class="field"><label>Producto</label><select name="product_id" required><option value="">Seleccionar</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?> · <?= money($product['price']) ?> · stock <?= (int) $product['stock'] ?></option><?php endforeach; ?></select></div>
                    <div class="field"><label>Cantidad</label><input class="input" type="number" name="quantity" min="1" step="1" value="1" required></div>
                </div>
                <button class="btn btn-light" style="margin-top:16px" type="submit">+ Agregar a la cuenta</button>
            </form>
        </section>

        <section class="card pad">
            <h3 style="margin-top:0">Cuenta del cliente</h3>
            <div class="detail-list">
                <div class="detail-row"><span><?= e($appointment['service_name']) ?></span><strong><?= money($appointment['price_at_booking']) ?></strong></div>
                <?php foreach ($lines as $line): ?>
                    <div class="detail-row">
                        <span><?= (int) $line['quantity'] ?> × <?= e($line['product_name']) ?></span>
                        <div style="display:flex;align-items:center;gap:8px">
                            <strong><?= money((int) $line['quantity'] * (int) $line['unit_price']) ?></strong>
                            <form method="post" onsubmit="return confirm('¿Quitar este producto?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="action" value="remove_product">
                                <input type="hidden" name="line_id" value="<?= (int) $line['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit">×</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="detail-row total-row"><span>Total</span><strong><?= money($total) ?></strong></div>
                <div class="detail-row"><span>Pago</span><span class="badge <?= $appointment['paid'] ? 'success' : 'warning' ?>"><?= $appointment['paid'] ? 'Pagado' : 'Pendiente' ?></span></div>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
