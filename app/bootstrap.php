<?php
/**
 * Punkt startowy aplikacji: konfiguracja, sesja, połączenie z bazą,
 * repozytoria i funkcje pomocnicze.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config/config.php';

date_default_timezone_set($config['app']['timezone']);

// --- Sesja -----------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_name($config['app']['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $isHttps,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Stałe wygodne w widokach ----------------------------------------
define('APP_NAME', $config['app']['name']);
define('BASE_URL', $config['app']['base_url']);
define('CURRENCY', $config['app']['currency']);

require APP_ROOT . '/app/Database.php';
require APP_ROOT . '/app/helpers.php';
require APP_ROOT . '/app/csrf.php';
require APP_ROOT . '/app/auth.php';
require APP_ROOT . '/app/UserRepository.php';
require APP_ROOT . '/app/RoomRepository.php';
require APP_ROOT . '/app/ReservationRepository.php';
require APP_ROOT . '/app/Validator.php';

// --- Baza danych -----------------------------------------------------
try {
    $pdo = Database::connect($config['db']);
} catch (Throwable $e) {
    http_response_code(500);
    $message = $config['app']['debug']
        ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Spróbuj ponownie później.';
    echo '<!doctype html><html lang="pl"><head><meta charset="utf-8">'
        . '<title>Błąd bazy danych</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
        . '</head><body class="bg-light d-flex align-items-center" style="min-height:100vh">'
        . '<div class="container"><div class="alert alert-danger shadow-sm">'
        . '<h1 class="h4 mb-2">Brak połączenia z bazą danych</h1>'
        . '<p class="mb-1">' . $message . '</p>'
        . '<hr><p class="mb-0 small">Sprawdź plik <code>config/config.php</code> oraz to, czy baza '
        . '<code>' . htmlspecialchars((string) $config['db']['name'], ENT_QUOTES, 'UTF-8') . '</code> została zaimportowana.</p>'
        . '</div></div></body></html>';
    exit;
}

$users        = new UserRepository($pdo);
$rooms        = new RoomRepository($pdo);
$reservations = new ReservationRepository($pdo);
