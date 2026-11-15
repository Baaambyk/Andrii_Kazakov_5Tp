<?php

declare(strict_types=1);

/**
 * Uwierzytelnianie i autoryzacja oparte na sesji.
 */

function auth_user(bool $refresh = false): ?array
{
    global $users;

    static $cached = null;

    if ($refresh) {
        $cached = null;
    }

    if ($cached !== null) {
        return $cached;
    }

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }

    $user = $users->find($id);
    if ($user === null) {
        logout_user();

        return null;
    }

    return $cached = $user;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_id(): int
{
    return (int) (auth_user()['id'] ?? 0);
}

function is_admin(): bool
{
    return (auth_user()['role'] ?? '') === 'admin';
}

/** Logowanie: nowe ID sesji chroni przed session fixation. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']  = (int) $user['id'];
    $_SESSION['login_at'] = time();
    unset($_SESSION['_login_attempts'], $_SESSION['_csrf']);
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['login_at']);
    session_regenerate_id(true);
}

function require_login(): void
{
    if (auth_check()) {
        return;
    }

    flash('warning', 'Zaloguj się, aby kontynuować.');
    $next = $_SERVER['REQUEST_URI'] ?? '/reservations.php';
    redirect('login.php?next=' . urlencode($next));
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        abort_page(403, 'Brak dostępu', 'Ta sekcja jest dostępna wyłącznie dla administratorów.');
    }
}

function require_guest(): void
{
    if (auth_check()) {
        redirect('reservations.php');
    }
}

// --- Proste ograniczenie liczby prób logowania ------------------------

function login_locked_seconds(): int
{
    $until = (int) ($_SESSION['_login_attempts']['until'] ?? 0);

    return max(0, $until - time());
}

function login_failed(): void
{
    $attempts = $_SESSION['_login_attempts'] ?? ['count' => 0, 'until' => 0];
    $attempts['count']++;

    if ($attempts['count'] >= 5) {
        $attempts['count'] = 0;
        $attempts['until'] = time() + 300; // 5 minut blokady
    }

    $_SESSION['_login_attempts'] = $attempts;
}

function login_succeeded(): void
{
    unset($_SESSION['_login_attempts']);
}
