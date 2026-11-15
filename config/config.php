<?php
/**
 * Konfiguracja aplikacji BookIt.
 * Wartości można nadpisać zmiennymi środowiskowymi (wygodne przy wdrożeniu).
 */

declare(strict_types=1);

/**
 * Automatyczne wykrywanie adresu bazowego:
 * działa zarówno dla `php -S -t public`, jak i dla XAMPP w podkatalogu.
 * Można wymusić wartość zmienną BASE_URL lub ręcznie w 'base_url'.
 */
$autoBaseUrl = '';
$documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$publicDir = realpath(dirname(__DIR__) . '/public');

if ($documentRoot !== false && $publicDir !== false) {
    $normalizedRoot = strtolower(str_replace('\\', '/', $documentRoot));
    $normalizedPublic = strtolower(str_replace('\\', '/', $publicDir));

    if (str_starts_with($normalizedPublic, $normalizedRoot)) {
        $autoBaseUrl = rtrim(substr($normalizedPublic, strlen($normalizedRoot)), '/');
    }
}

$envBaseUrl = getenv('BASE_URL');

return [
    'app' => [
        'name'         => 'BookIt',
        'tagline'      => 'Rezerwacje sal bez stresu',
        'timezone'     => 'Europe/Warsaw',
        'locale'       => 'pl_PL',
        'currency'     => 'zł',
        'session_name' => 'bookit_session',
        // Ręczne nadpisanie, np. '/bookit/public'. Puste = autodetekcja.
        'base_url'     => $envBaseUrl !== false && $envBaseUrl !== ''
            ? rtrim($envBaseUrl, '/')
            : $autoBaseUrl,
        'debug'        => filter_var(getenv('APP_DEBUG') ?: '1', FILTER_VALIDATE_BOOL),
        'upload_dir'   => dirname(__DIR__) . '/public/uploads/rooms',
        'upload_url'   => 'uploads/rooms',
        'max_upload'   => 2 * 1024 * 1024, // 2 MB
    ],
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => (int) (getenv('DB_PORT') ?: 3306),
        'name'    => getenv('DB_NAME') ?: 'bookit',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
