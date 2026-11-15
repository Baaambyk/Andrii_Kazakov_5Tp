<?php
/**
 * Wspólny nagłówek: <head>, nawigacja i komunikaty flash.
 * Strony mogą ustawić $pageTitle, $pageDescription oraz $bodyClass.
 */
$currentUser = auth_user();
$title = isset($pageTitle) ? page_title($pageTitle) : APP_NAME . ' — ' . ($config['app']['tagline'] ?? '');
$description = $pageDescription ?? 'BookIt — nowoczesny system rezerwacji sal konferencyjnych. Sprawdź dostępność, zarezerwuj online i zarządzaj spotkaniami.';
?>
<!doctype html>
<html lang="pl" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="#4f46e5">
    <title><?= e($title) ?></title>

    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/style.css')) ?>" rel="stylesheet">

    <script>
        // Motyw ustawiany przed renderowaniem, aby uniknąć mignięcia.
        (function () {
            try {
                var theme = localStorage.getItem('bookit-theme');
                if (theme === 'dark' || theme === 'light') {
                    document.documentElement.setAttribute('data-bs-theme', theme);
                }
            } catch (e) { /* localStorage może być zablokowany */ }
        })();
    </script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="visually-hidden-focusable skip-link" href="#main-content">Przejdź do treści</a>

<nav class="navbar navbar-expand-lg sticky-top app-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
            <span class="brand-mark"><i class="bi bi-calendar2-check"></i></span>
            <span class="brand-text">Book<span>It</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <?php $isAdminSection = str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/admin/'); ?>
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= !$isAdminSection ? nav_active('index.php') : '' ?>" href="<?= e(url('index.php')) ?>">Start</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('rooms.php', 'room.php') ?>" href="<?= e(url('rooms.php')) ?>">Sale</a>
                </li>
                <?php if ($currentUser !== null): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= nav_active('reservations.php', 'reservation-edit.php') ?>"
                           href="<?= e(url('reservations.php')) ?>">Moje rezerwacje</a>
                    </li>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $isAdminSection ? 'active' : '' ?>"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Administracja
                        </a>
                        <ul class="dropdown-menu shadow-sm border-0">
                            <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>"><i class="bi bi-speedometer2 me-2"></i>Pulpit</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('admin/rooms.php')) ?>"><i class="bi bi-door-open me-2"></i>Sale</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('admin/reservations.php')) ?>"><i class="bi bi-calendar3 me-2"></i>Rezerwacje</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people me-2"></i>Użytkownicy</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button class="btn btn-icon theme-toggle" type="button" data-theme-toggle
                        aria-label="Przełącz motyw" title="Przełącz motyw">
                    <i class="bi bi-moon-stars"></i>
                </button>

                <?php if ($currentUser !== null): ?>
                    <div class="dropdown">
                        <button class="btn user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar"><?= e(initials((string) $currentUser['name'])) ?></span>
                            <span class="d-none d-sm-inline"><?= e(explode(' ', (string) $currentUser['name'])[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li class="px-3 py-2">
                                <div class="fw-semibold"><?= e($currentUser['name']) ?></div>
                                <div class="small text-secondary text-truncate" style="max-width: 220px"><?= e($currentUser['email']) ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person-gear me-2"></i>Moje konto</a></li>
                            <li><a class="dropdown-item" href="<?= e(url('reservations.php')) ?>"><i class="bi bi-calendar2-week me-2"></i>Moje rezerwacje</a></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>"><i class="bi bi-shield-lock me-2"></i>Panel administratora</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= e(url('logout.php')) ?>" class="px-3 py-2">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                        <i class="bi bi-box-arrow-right me-1"></i> Wyloguj się
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-link text-decoration-none text-body d-none d-sm-inline-flex"
                       href="<?= e(url('login.php')) ?>">Zaloguj się</a>
                    <a class="btn btn-primary btn-sm px-3" href="<?= e(url('register.php')) ?>">
                        <i class="bi bi-person-plus me-1"></i> Załóż konto
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<?php $flashes = pull_flashes(); ?>
<?php if ($flashes !== []): ?>
    <div class="container flash-stack">
        <?php foreach ($flashes as $flash): ?>
            <?php
            $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
            $icon = match ($type) {
                'success' => 'check-circle',
                'danger'  => 'exclamation-octagon',
                'warning' => 'exclamation-triangle',
                default   => 'info-circle',
            };
            ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show shadow-sm" role="alert" data-autohide>
                <i class="bi bi-<?= e($icon) ?> me-2"></i><?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="main-content">
