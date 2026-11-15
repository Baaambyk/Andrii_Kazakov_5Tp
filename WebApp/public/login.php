<?php
require __DIR__ . '/../app/bootstrap.php';

require_guest();

$errors = [];
$email = '';
$next = safe_next((string) ($_POST['next'] ?? $_GET['next'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remaining = login_locked_seconds();

    if ($remaining > 0) {
        $errors['password'] = 'Zbyt wiele nieudanych prób. Spróbuj ponownie za ' . (int) ceil($remaining / 60) . ' min.';
    } else {
        $v = new Validator();
        $v->required('email', $email, 'Podaj adres e-mail.')
            ->email('email', $email)
            ->required('password', $password, 'Podaj hasło.');

        if ($v->passes()) {
            $user = $users->findByEmail($email);

            if ($user !== null && password_verify($password, (string) $user['password_hash'])) {
                if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
                    $users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
                }

                login_succeeded();
                login_user($user);
                flash('success', 'Witaj ponownie, ' . explode(' ', (string) $user['name'])[0] . '!');
                redirect($next);
            }

            login_failed();
            $errors['password'] = 'Nieprawidłowy e-mail lub hasło.';
        } else {
            $errors = $v->errors();
        }
    }
}

$pageTitle = 'Zaloguj się';
$bodyClass = 'auth-page-body';

require __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center g-4">
            <div class="col-lg-5 d-none d-lg-block">
                <div class="auth-visual d-flex flex-column">
                    <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-4" href="<?= e(url('index.php')) ?>">
                        <span class="brand-mark"><i class="bi bi-calendar2-check"></i></span>
                        <span class="brand-text text-white">Book<span style="color:#a5b4fc">It</span></span>
                    </a>
                    <h2 class="h3">Witaj z powrotem!</h2>
                    <p style="color: rgba(255,255,255,.75)">
                        Zaloguj się, aby zobaczyć swoje rezerwacje, zarządzać terminami
                        i rezerwować kolejne sale bez wypełniania danych od nowa.
                    </p>
                    <ul class="mt-auto">
                        <li><i class="bi bi-check-circle-fill"></i> Podgląd wszystkich rezerwacji</li>
                        <li><i class="bi bi-check-circle-fill"></i> Sprawdzanie dostępności w czasie rzeczywistym</li>
                        <li><i class="bi bi-check-circle-fill"></i> Bezpieczne hasła i sesje</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-8 col-lg-5">
                <div class="auth-card">
                    <div class="mb-4">
                        <span class="eyebrow"><i class="bi bi-person-lock"></i> Konto użytkownika</span>
                        <h1 class="h3 fw-bold mt-2 mb-2">Zaloguj się</h1>
                        <p class="text-muted-soft mb-0">Wpisz dane, aby przejść do swojego konta.</p>
                    </div>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert-danger py-2 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <?= e(reset($errors)) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('login.php')) ?>" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <input type="hidden" name="next" value="<?= e($next) ?>">

                        <div class="mb-3">
                            <label class="form-label" for="email">Adres e-mail</label>
                            <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   type="email" id="email" name="email" value="<?= e($email) ?>"
                                   placeholder="twoj@email.pl" autocomplete="email" required>
                            <div class="invalid-feedback">Podaj poprawny adres e-mail.</div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Hasło</label>
                                <span class="small text-muted-soft">min. 8 znaków</span>
                            </div>
                            <div class="input-group has-validation">
                                <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       type="password" id="password" name="password" placeholder="••••••••"
                                       autocomplete="current-password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        data-password-toggle="password" aria-label="Pokaż hasło">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="invalid-feedback"><?= e($errors['password'] ?? 'Podaj hasło.') ?></div>
                            </div>
                        </div>

                        <button class="btn btn-primary w-100 btn-lg" type="submit">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Zaloguj się
                        </button>
                    </form>

                    <div class="alert alert-secondary small mt-4 mb-0">
                        <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Konta demonstracyjne</div>
                        <div>Administrator: <code>admin@bookit.pl</code> / <code>Admin123!</code></div>
                        <div>Użytkownik: <code>jan@example.com</code> / <code>User123!</code></div>
                    </div>

                    <p class="text-center text-muted-soft small mt-4 mb-0">
                        Nie masz konta?
                        <a href="<?= e(url('register.php')) ?>" class="fw-semibold">Zarejestruj się</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
