<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$token = trim((string) ($_GET['token'] ?? ''));
$stmt = db()->prepare(
    'SELECT a.*, c.name AS customer_name, c.phone, s.name AS service_name, b.name AS barber_name
     FROM appointments a
     INNER JOIN customers c ON c.id = a.customer_id
     INNER JOIN services s ON s.id = a.service_id
     INNER JOIN barbers b ON b.id = a.barber_id
     WHERE a.public_token = ?'
);
$stmt->execute([$token]);
$appointment = $stmt->fetch();

if (!$appointment) {
    http_response_code(404);
    exit('Turno no encontrado.');
}

$pageTitle = 'Turno confirmado';
require __DIR__ . '/_header.php';
?>
<main class="page">
    <div class="container narrow">
        <section class="card confirmation">
            <div class="confirm-icon">✓</div>
            <h1>¡Turno confirmado!</h1>
            <p>Guardá este enlace. Desde acá podés revisar los datos o cancelar el turno.</p>

            <div class="detail-list" style="text-align:left;max-width:520px;margin:0 auto 26px">
                <div class="detail-row"><span>Cliente</span><strong><?= e($appointment['customer_name']) ?></strong></div>
                <div class="detail-row"><span>Servicio</span><strong><?= e($appointment['service_name']) ?></strong></div>
                <div class="detail-row"><span>Peluquero</span><strong><?= e($appointment['barber_name']) ?></strong></div>
                <div class="detail-row"><span>Fecha</span><strong><?= date('d/m/Y', strtotime($appointment['appointment_date'])) ?></strong></div>
                <div class="detail-row"><span>Horario</span><strong><?= substr($appointment['start_time'], 0, 5) ?></strong></div>
                <div class="detail-row"><span>Precio</span><strong><?= money($appointment['price_at_booking']) ?></strong></div>
                <div class="detail-row"><span>Estado</span><span class="badge <?= e(status_class($appointment['status'])) ?>"><?= e(status_label($appointment['status'])) ?></span></div>
            </div>

            <?php if (!in_array($appointment['status'], ['cancelled', 'completed', 'no_show'], true)): ?>
                <form method="post" action="/cancelar.php" onsubmit="return confirm('¿Seguro que querés cancelar el turno?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <button class="btn btn-danger" type="submit">Cancelar turno</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require __DIR__ . '/_footer.php'; ?>
