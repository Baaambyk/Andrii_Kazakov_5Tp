<?php

declare(strict_types=1);

/**
 * Ochrona formularzy przed atakami CSRF.
 */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';

    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        abort_page(
            419,
            'Sesja wygasła',
            'Formularz wygasł lub został wysłany z nieprawidłowego miejsca. Odśwież stronę i spróbuj ponownie.'
        );
    }
}

/**
 * Samodzielna strona błędu (nie wymaga szablonu nagłówka).
 */
function abort_page(int $code, string $title, string $message): never
{
    http_response_code($code);
    ?>
    <!doctype html>
    <html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="<?= e(asset('assets/css/style.css')) ?>" rel="stylesheet">
    </head>
    <body class="bg-body-tertiary">
        <div class="container py-5">
            <div class="row justify-content-center py-5">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5 text-center">
                            <div class="display-5 fw-bold text-primary mb-2"><?= $code ?></div>
                            <h1 class="h3 mb-3"><?= e($title) ?></h1>
                            <p class="text-secondary mb-4"><?= e($message) ?></p>
                            <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-1"></i> Wróć na stronę główną
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
