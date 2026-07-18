<?php

declare(strict_types=1);

function admin_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }

    return $user;
}

function require_admin(): void
{
    if (!admin_user()) {
        flash('warning', 'Iniciá sesión para continuar.');
        redirect('/admin/login.php');
    }
}
