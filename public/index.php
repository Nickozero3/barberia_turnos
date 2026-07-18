<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$services = db()->query('SELECT * FROM services WHERE active = 1 ORDER BY price')->fetchAll();
$barbers = db()->query('SELECT * FROM barbers WHERE active = 1 ORDER BY name')->fetchAll();
$pageTitle = setting('business_name', 'fioreee_barber');
require __DIR__ . '/_header.php';
?>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="eyebrow">● Reservas online disponibles</span>
            <h1>Reservá tu corte con Fiorella.</h1>
            <p><?= e(setting('business_subtitle', 'Elegí la fecha y el horario que mejor te quede.')) ?></p>
            <div class="header-actions">
                <a class="btn btn-accent" href="/reservar.php">Elegir un horario</a>
                <a class="btn btn-light" href="#servicios">Ver servicios</a>
            </div>
        </div>
        <aside class="hero-card">
            <?php foreach (array_slice($services, 0, 3) as $service): ?>
                <div class="hero-card-row">
                    <div>
                        <small><?= (int) $service['duration_minutes'] ?> minutos</small>
                        <strong><?= e($service['name']) ?></strong>
                    </div>
                    <span class="hero-price"><?= money($service['price']) ?></span>
                </div>
            <?php endforeach; ?>
        </aside>
    </div>
</section>

<section id="servicios" class="section">
    <div class="container">
        <div class="section-title">
            <div>
                <h2>Servicios</h2>
                <p>Precios claros y horarios reservables desde cualquier dispositivo.</p>
            </div>
        </div>
        <div class="grid cols-3">
            <?php foreach ($services as $service): ?>
                <article class="card hover service-card">
                    <span class="icon-box">✂️</span>
                    <h3><?= e($service['name']) ?></h3>
                    <p><?= e($service['description']) ?></p>
                    <div class="service-meta">
                        <div>
                            <div class="service-price"><?= money($service['price']) ?></div>
                            <div class="service-duration"><?= (int) $service['duration_minutes'] ?> minutos</div>
                        </div>
                        <a class="btn btn-primary btn-sm" href="/reservar.php?service_id=<?= (int) $service['id'] ?>">Reservar</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="background:#fff">
    <div class="container">
        <div class="section-title">
            <div>
                <h2>Tu peluquera</h2>
                <p>Fiorella será quien realice tu corte.</p>
            </div>
        </div>
        <div class="grid cols-3">
            <?php foreach ($barbers as $barber): ?>
                <article class="card pad hover">
                    <div class="icon-box" style="background:<?= e($barber['color']) ?>;color:#fff">✦</div>
                    <h3><?= e($barber['name']) ?></h3>
                    <p style="color:var(--muted)"><?= e($barber['bio']) ?></p>
                    <a class="btn btn-light btn-sm" href="/reservar.php?barber_id=<?= (int) $barber['id'] ?>">Ver horarios</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
