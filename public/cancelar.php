<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

verify_csrf();
$token = trim((string) ($_POST['token'] ?? ''));
$stmt = db()->prepare("UPDATE appointments SET status = 'cancelled' WHERE public_token = ? AND status NOT IN ('completed', 'no_show')");
$stmt->execute([$token]);
redirect('/confirmacion.php?token=' . urlencode($token));
