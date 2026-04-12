<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function _auth_secret(): string {
    static $s = null;
    if ($s === null) {
        $s = load_settings();
    }
    return $s['auth_secret'] ?? '';
}

function is_authenticated(): bool {
    $secret = _auth_secret();
    if (!$secret) return false;
    $cookie = $_COOKIE['art_auth'] ?? '';
    if (!$cookie) return false;
    $expected = hash_hmac('sha256', 'art_admin_ok', $secret);
    return hash_equals($expected, $cookie);
}

function csrf_token(): string {
    return hash_hmac('sha256', 'csrf_art_token', _auth_secret());
}

function verify_csrf(string $token): bool {
    if (!$token) return false;
    return hash_equals(csrf_token(), $token);
}

function require_auth(): void {
    if (!is_authenticated()) {
        header('Location: /admin/');
        exit;
    }
}
