<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();
$errors = [];
$services = $pdo->query('SELECT * FROM services WHERE active = 1 ORDER BY name')->fetchAll();
$barbers = $pdo->query('SELECT * FROM barbers WHERE active = 1 ORDER BY name')->fetchAll();
$defaultServiceId = isset($services[0]) ? (int) $services[0]['id'] : 0;
$defaultBarberId = isset($barbers[0]) ? (int) $barbers[0]['id'] : 0;
$selectedService = (int) ($_GET['service_id'] ?? $_POST['service_id'] ?? $defaultServiceId);
$selectedBarber = (int) ($_GET['barber_id'] ?? $_POST['barber_id'] ?? $defaultBarberId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $date = trim((string) ($_POST['appointment_date'] ?? ''));
    $slotData = explode('|', (string) ($_POST['slot'] ?? ''));
    $time = $slotData[0] ?? '';
    $barberId = (int) ($slotData[1] ?? 0);
    $name = trim((string) ($_POST['customer_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($serviceId < 1) $errors[] = 'Elegí un servicio.';
    if ($barberId < 1 || !preg_match('/^\d{2}:\d{2}$/', $time)) $errors[] = 'Elegí un horario disponible.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Elegí una fecha válida.';
    if (mb_strlen($name) < 2) $errors[] = 'Ingresá tu nombre.';
    if (mb_strlen(normalized_phone($phone)) < 8) $errors[] = 'Ingresá un teléfono válido.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo no es válido.';

    $serviceStmt = $pdo->prepare('SELECT * FROM services WHERE id = ? AND active = 1');
    $serviceStmt->execute([$serviceId]);
    $service = $serviceStmt->fetch();
    if (!$service) $errors[] = 'El servicio seleccionado ya no está disponible.';

    if (!$errors && !slot_is_available($pdo, $barberId, $serviceId, $date, $time)) {
        $errors[] = 'Ese horario acaba de ocuparse. Elegí otro.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $phoneClean = normalized_phone($phone);
            $customerStmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ? ORDER BY id DESC LIMIT 1');
            $customerStmt->execute([$phoneClean]);
            $customerId = (int) ($customerStmt->fetchColumn() ?: 0);

            if ($customerId > 0) {
                $updateCustomer = $pdo->prepare('UPDATE customers SET name = ?, email = ? WHERE id = ?');
                $updateCustomer->execute([$name, $email ?: null, $customerId]);
            } else {
                $insertCustomer = $pdo->prepare('INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)');
                $insertCustomer->execute([$name, $phoneClean, $email ?: null]);
                $customerId = (int) $pdo->lastInsertId();
            }

            if (!slot_is_available($pdo, $barberId, $serviceId, $date, $time)) {
                throw new RuntimeException('El horario dejó de estar disponible.');
            }

            $startMinutes = time_to_minutes($time);
            $endTime = minutes_to_time($startMinutes + (int) $service['duration_minutes']);
            $token = generate_public_token();

            $insert = $pdo->prepare(
                'INSERT INTO appointments
                (customer_id, barber_id, service_id, appointment_date, start_time, end_time, price_at_booking, status, notes, public_token)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $customerId,
                $barberId,
                $serviceId,
                $date,
                $time,
                $endTime,
                (int) $service['price'],
                'confirmed',
                $notes ?: null,
                $token,
            ]);

            $pdo->commit();
            redirect('/confirmacion.php?token=' . urlencode($token));
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'No pudimos guardar el turno. Intentá nuevamente.';
        }
    }
}

$pageTitle = 'Reservar turno';
require __DIR__ . '/_header.php';
?>

<main class="page">
    <div class="container narrow">
        <div class="section-title">
            <div>
                <h2>Reservar turno</h2>
                <p>Completá los pasos y confirmá tu horario.</p>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="alert danger">
                <strong>Revisá los datos:</strong><br>
                <?= implode('<br>', array_map('e', $errors)) ?>
            </div>
        <?php endif; ?>

        <form class="card form-card" method="post">
            <?= csrf_field() ?>

            <section class="form-section">
                <h3>1. Tu corte</h3>
                <?php if (count($services) === 1 && count($barbers) === 1): ?>
                    <?php $onlyService = $services[0]; $onlyBarber = $barbers[0]; ?>
                    <input id="service_id" type="hidden" name="service_id" value="<?= (int) $onlyService['id'] ?>">
                    <input id="barber_id" type="hidden" name="barber_id" value="<?= (int) $onlyBarber['id'] ?>">
                    <div class="selection-summary">
                        <span class="icon-box">✂️</span>
                        <div>
                            <strong><?= e($onlyService['name']) ?></strong>
                            <p><?= money($onlyService['price']) ?> · <?= (int) $onlyService['duration_minutes'] ?> minutos · con <?= e($onlyBarber['name']) ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <p>Elegí el servicio y la profesional.</p>
                    <div class="form-grid">
                        <div class="field">
                            <label for="service_id">Servicio</label>
                            <select id="service_id" name="service_id" required>
                                <option value="">Seleccionar servicio</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?= (int) $service['id'] ?>" <?= $selectedService === (int) $service['id'] ? 'selected' : '' ?>>
                                        <?= e($service['name']) ?> · <?= money($service['price']) ?> · <?= (int) $service['duration_minutes'] ?> min
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="barber_id">Peluquera</label>
                            <select id="barber_id" name="barber_id">
                                <?php foreach ($barbers as $barber): ?>
                                    <option value="<?= (int) $barber['id'] ?>" <?= $selectedBarber === (int) $barber['id'] ? 'selected' : '' ?>><?= e($barber['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="form-section">
                <h3>2. Fecha y horario</h3>
                <p>Solo se muestran horarios realmente disponibles.</p>
                <div class="field" style="margin-bottom:16px">
                    <label for="appointment_date">Fecha</label>
                    <input class="input" type="date" id="appointment_date" name="appointment_date" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['appointment_date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>" required>
                </div>
                <div id="slots" class="slots">
                    <div class="empty-box">Cargando horarios disponibles…</div>
                </div>
            </section>

            <section class="form-section">
                <h3>3. Tus datos</h3>
                <p>Usaremos el teléfono para identificar tu reserva.</p>
                <div class="form-grid">
                    <div class="field">
                        <label for="customer_name">Nombre y apellido</label>
                        <input class="input" id="customer_name" name="customer_name" value="<?= e($_POST['customer_name'] ?? '') ?>" required>
                    </div>
                    <div class="field">
                        <label for="phone">Teléfono</label>
                        <input class="input" id="phone" name="phone" inputmode="tel" placeholder="351..." value="<?= e($_POST['phone'] ?? '') ?>" required>
                    </div>
                    <div class="field full">
                        <label for="email">Correo <span style="color:var(--muted);font-weight:500">(opcional)</span></label>
                        <input class="input" id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="field full">
                        <label for="notes">Comentario <span style="color:var(--muted);font-weight:500">(opcional)</span></label>
                        <textarea id="notes" name="notes" placeholder="Ejemplo: quiero un degradado bajo"><?= e($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>

            <div class="form-section">
                <button id="booking-submit" class="btn btn-primary btn-block" type="submit" disabled>Confirmar turno</button>
                <p style="margin:12px 0 0;text-align:center;color:var(--muted);font-size:13px"><?= e(setting('booking_notice', 'Llegá 5 minutos antes.')) ?></p>
            </div>
        </form>
    </div>
</main>

<script src="/assets/js/booking.js"></script>
<?php require __DIR__ . '/_footer.php'; ?>
