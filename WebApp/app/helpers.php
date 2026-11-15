<?php

declare(strict_types=1);

/**
 * Funkcje pomocnicze używane w całej aplikacji.
 */

/** Bezpieczne wypisanie tekstu w HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Adres wewnątrz aplikacji (z uwzględnieniem BASE_URL). */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path === '' ? '/' : '/' . $path);
}

/** Adres pliku statycznego z automatycznym cache-bustingiem. */
function asset(string $path): string
{
    $file = APP_ROOT . '/public/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';

    return url($path) . $version;
}

/** Przekierowanie i zakończenie skryptu. */
function redirect(string $path): never
{
    if (preg_match('#^https?://#i', $path)) {
        $target = $path;
    } else {
        if (BASE_URL !== '' && str_starts_with($path, BASE_URL . '/')) {
            $path = substr($path, strlen(BASE_URL));
        }
        $target = url($path);
    }

    header('Location: ' . $target);
    exit;
}

/** Zapisanie komunikatu typu flash. */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** Pobranie i wyczyszczenie komunikatów flash. */
function pull_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $flashes;
}

/** Kwota w polskim formacie. */
function money(int|float|string|null $value): string
{
    $value = (float) $value;
    $decimals = abs($value - round($value)) > 0.001 ? 2 : 0;

    return number_format($value, $decimals, ',', ' ') . ' ' . CURRENCY;
}

/** Data w formacie dd.mm.rrrr (opcjonalnie z dniem tygodnia). */
function pl_date(string $date, bool $withWeekday = false): string
{
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }

    $result = date('d.m.Y', $ts);
    if ($withWeekday) {
        $weekdays = ['pon.', 'wt.', 'śr.', 'czw.', 'pt.', 'sob.', 'niedz.'];
        $result = $weekdays[(int) date('N', $ts) - 1] . ', ' . $result;
    }

    return $result;
}

/** Godzina w formacie HH:MM. */
function short_time(string $time): string
{
    $ts = strtotime($time);

    return $ts === false ? $time : date('H:i', $ts);
}

/** Liczba godzin między dwiema godzinami. */
function hours_between(string $start, string $end): float
{
    $diff = (strtotime($end) ?: 0) - (strtotime($start) ?: 0);

    return max(0, round($diff / 3600, 2));
}

/** Zamiana godziny HH:MM na minuty od północy. */
function time_to_minutes(string $time): ?int
{
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
        return null;
    }

    return ((int) $m[1]) * 60 + (int) $m[2];
}

/** Etykieta statusu rezerwacji. */
function status_label(string $status): string
{
    return match ($status) {
        'confirmed' => 'Potwierdzona',
        'cancelled' => 'Anulowana',
        default     => 'Oczekuje',
    };
}

/** Klasa Bootstrap dla statusu. */
function status_class(string $status): string
{
    return match ($status) {
        'confirmed' => 'text-bg-success',
        'cancelled' => 'text-bg-secondary',
        default     => 'text-bg-warning',
    };
}

/** Adres zdjęcia sali (z obrazem zastępczym). */
function room_image(array $room): string
{
    $image = trim((string) ($room['image'] ?? ''));
    if ($image === '') {
        return asset('assets/img/room-default.jpg');
    }

    return preg_match('#^https?://#i', $image) ? $image : url($image);
}

/** Inicjały do awatara. */
function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : '?';
}

/** Lista godzin do wyboru (co 30 minut). */
function time_options(string $from = '08:00', string $to = '22:00'): array
{
    $options = [];
    $cursor = strtotime($from);
    $end = strtotime($to);

    while ($cursor !== false && $end !== false && $cursor <= $end) {
        $options[] = date('H:i', $cursor);
        $cursor += 30 * 60;
    }

    return $options;
}

/** Czy podany adres jest bieżącą stroną? (do podświetlania menu) */
function nav_active(string ...$files): string
{
    $current = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

    return in_array($current, $files, true) ? 'active' : '';
}

/** Wartość pola formularza po błędnej walidacji. */
function old(array $source, string $key, mixed $default = ''): string
{
    return e($source[$key] ?? $default);
}

/** Zaznaczenie opcji w <select>. */
function selected(mixed $value, mixed $expected): string
{
    return (string) $value === (string) $expected ? 'selected' : '';
}

/** Łączenie parametrów zapytania z zachowaniem filtrów. */
function query_with(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);

    return http_build_query($params);
}

/** Skrócenie długiego tekstu. */
function excerpt(string $text, int $limit = 120): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

/** Bezpieczny adres powrotny po logowaniu. */
function safe_next(string $next): string
{
    $next = trim($next);

    if (BASE_URL !== '' && str_starts_with($next, BASE_URL . '/')) {
        $next = substr($next, strlen(BASE_URL));
    }

    if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
        return 'reservations.php';
    }

    return ltrim($next, '/');
}

/** Tytuł strony. */
function page_title(string $title): string
{
    return $title . ' · ' . APP_NAME;
}
