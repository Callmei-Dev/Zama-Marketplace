<?php
// includes/csrf.php
declare(strict_types=1);
session_start();

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
