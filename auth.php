<?php
// includes/auth.php
declare(strict_types=1);

// Simple session-based auth helpers used across the website.


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//Ensure session is started (idempotent).
 
function session_start_if_needed(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Log in a user by id. Sets minimal session values.

function login_user_by_id(int $userId): void {
    session_start_if_needed();
    // Regenerate session id
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in_at'] = time();
}

//session destroy
function logout_current_user(): void {
    session_start_if_needed();
    // Unset all session variables
    $_SESSION = [];
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

//Return true if a user is logged in.
function is_logged_in(): bool {
    session_start_if_needed();
    return !empty($_SESSION['user_id']);
}

/**
 * Return current user record as associative array or null if not logged in.
 * We fetch fresh data from the database keeping values up to date.
 */
function current_user(): ?array {
    session_start_if_needed();
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $userId = (int)$_SESSION['user_id'];

    // Lazy-load PDO if not already included
    if (!isset($GLOBALS['pdo'])) {
        // Attempt to include db.php relative to includes/auth.php
        $dbPath = __DIR__ . '/db.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }
    }

    if (!isset($GLOBALS['pdo']) || !$GLOBALS['pdo'] instanceof PDO) {
        // As a fallback, try $pdo in global scope
        $pdo = $GLOBALS['pdo'] ?? null;
    } else {
        $pdo = $GLOBALS['pdo'];
    }

    if (!$pdo instanceof PDO) {
        // Cannot fetch user without DB; return minimal session info
        return ['id' => $userId];
    }

    $stmt = $pdo->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // User no longer exists; log out
        logout_current_user();
        return null;
    }
    return $user;
}

//equire login and redirect to login page if not authenticated.

function require_login(string $redirectTo = '/public/login.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}
