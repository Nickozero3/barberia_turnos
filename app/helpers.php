<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function money(int|float|string $value): string
{
    return '$' . number_format((float) $value, 0, ',', '.');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('La sesión venció. Volvé atrás e intentá nuevamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function setting(string $key, ?string $default = null): ?string
{
    static $settings = null;

    if ($settings === null) {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings[$key] ?? $default;
}

function status_label(string $status): string
{
    return [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'waiting' => 'En espera',
        'in_progress' => 'Atendiendo',
        'completed' => 'Finalizado',
        'cancelled' => 'Cancelado',
        'no_show' => 'No asistió',
    ][$status] ?? ucfirst($status);
}

function status_class(string $status): string
{
    return [
        'pending' => 'warning',
        'confirmed' => 'info',
        'waiting' => 'purple',
        'in_progress' => 'primary',
        'completed' => 'success',
        'cancelled' => 'danger',
        'no_show' => 'muted',
    ][$status] ?? 'muted';
}

function day_name(int $day): string
{
    return [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'][$day] ?? '';
}

function generate_public_token(): string
{
    return bin2hex(random_bytes(20));
}

function normalized_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?: '';
}

function phone_whatsapp_url(string $phone, string $message = ''): string
{
    $number = normalized_phone($phone);
    if ($number !== '' && !str_starts_with($number, '54')) {
        $number = '54' . ltrim($number, '0');
    }

    return 'https://wa.me/' . $number . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}
