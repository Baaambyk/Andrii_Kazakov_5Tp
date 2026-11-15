<?php
require __DIR__ . '/../app/bootstrap.php';

require_login();

$user = auth_user();
$profileErrors = [];
$passwordErrors = [];

$profile = [
    'name'  => (string) ($_POST['name'] ?? $user['name']),
    'phone' => (string) ($_POST['phone'] ?? $user['phone']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profile') {
        $v = new Validator();
        $v->required('name', $profile['name'], 'Podaj imię i nazwisko.')
            ->minLength('name', $profile['name'], 3, 'Imię i nazwisko muszą mieć co najmniej 3 znaki.')
            ->maxLength('name', $profile['name'], 80)
            ->maxLength('phone', $profile['phone'], 30);

        if ($profile['phone'] !== '' && !preg_match('/^[0-9+\s()-]{6,30}$/', $profile['phone'])) {
            $v->add('phone', 'Podaj poprawny numer telefonu.');
        }

        if ($v->passes()) {
            $users->updateProfile((int) $user['id'], $profile['name'], $profile['phone']);
            flash('success', 'Dane konta zostały zapisane.');
            redirect('profile.php');
        }

        $profileErrors = $v->errors();
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        $v = new Validator();
        $v->required('current_password', $current, 'Podaj obecne hasło.')
            ->required('new_password', $new, 'Podaj nowe hasło.')
            ->minLength('new_password', $new, 8, 'Nowe hasło musi mieć co najmniej 8 znaków.')
            ->maxLength('new_password', $new, 72);

        if ($new !== '' && (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new))) {
            $v->add('new_password', 'Hasło musi zawierać przynajmniej jedną literę i jedną cyfrę.');
        }
        if ($new !== '' && $confirm !== '' && $new !== $confirm) {
            $v->add('new_password_confirm', 'Nowe hasła nie są identyczne.');
        }
        if ($current !== '' && !password_verify($current, (string) $user['password_hash'])) {
            $v->add('current_password', 'Obecne hasło jest nieprawidłowe.');
        }

        if ($v->passes()) {
            $users->updatePassword((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));
            flash('success', 'Hasło zostało zmienione. Zaloguj się ponownie na innych urządzeniach.');
            redirect('profile.php');
        }

        $passwordErrors = $v->errors();
    }
}

$counts = $reservations->countsForUser((int) $user['id']);
$upcoming = $reservations->countUpcomingForUser((int) $user['id']);
$pageTitle = 'Moje konto';

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Start</a></li>
                <li class="breadcrumb-item active" aria-current="page">Moje konto</li>
            </ol>
        </nav>
        <h1 class="h2 mb-1">Moje konto</h1>
        <p class="text-muted-soft mb-0">Zarządzaj danymi osobowymi i bezpieczeństwem konta.</p>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="detail-panel mb-4">
                    <div class="profile-header mb-3">
                        <div class="profile-avatar"><?= e(initials((string) $user['name'])) ?></div>
                        <div>
                            <h2 class="h5 fw-bold mb-1"><?= e($user['name']) ?></h2>
                            <div class="text-muted-soft small"><?= e($user['email']) ?></div>
                            <span class="badge badge-soft-primary mt-2">
                                <?= $user['role'] === 'admin' ? 'Administrator' : 'Użytkownik' ?>
                            </span>
                        </div>
                    </div>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-muted-soft">Konto od</span>
                            <strong><?= e(pl_date((string) $user['created_at'])) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-muted-soft">Nadchodzące rezerwacje</span>
                            <strong><?= $upcoming ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-muted-soft">Wszystkie rezerwacje</span>
                            <strong><?= $counts['total'] ?></strong>
                        </li>
                    </ul>
                </div>

                <div class="detail-panel">
                    <h2 class="h6 fw-bold mb-2"><i class="bi bi-shield-check text-primary me-1"></i> Bezpieczeństwo</h2>
                    <p class="small text-muted-soft mb-0">
                        Hasła są przechowywane w postaci hashy bcrypt. Formularze chronione są tokenami CSRF,
                        a sesja posiada flagę <code>HttpOnly</code> i <code>SameSite</code>.
                    </p>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="detail-panel mb-4">
                    <h2 class="h5 fw-bold mb-1">Dane osobowe</h2>
                    <p class="small text-muted-soft mb-4">Adres e-mail jest powiązany z kontem i nie może zostać zmieniony.</p>

                    <?php if ($profileErrors !== []): ?>
                        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= e(reset($profileErrors)) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('profile.php')) ?>" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="profile">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Imię i nazwisko</label>
                                <input class="form-control <?= isset($profileErrors['name']) ? 'is-invalid' : '' ?>"
                                       type="text" id="name" name="name" value="<?= e($profile['name']) ?>"
                                       maxlength="80" required>
                                <div class="invalid-feedback"><?= e($profileErrors['name'] ?? 'Podaj imię i nazwisko.') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Adres e-mail</label>
                                <input class="form-control" type="email" id="email" value="<?= e($user['email']) ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefon</label>
                                <input class="form-control <?= isset($profileErrors['phone']) ? 'is-invalid' : '' ?>"
                                       type="tel" id="phone" name="phone" value="<?= e($profile['phone']) ?>" maxlength="30">
                                <div class="invalid-feedback"><?= e($profileErrors['phone'] ?? 'Podaj poprawny numer telefonu.') ?></div>
                            </div>
                        </div>

                        <button class="btn btn-primary mt-4" type="submit">
                            <i class="bi bi-check2 me-1"></i> Zapisz dane
                        </button>
                    </form>
                </div>

                <div class="detail-panel">
                    <h2 class="h5 fw-bold mb-1">Zmiana hasła</h2>
                    <p class="small text-muted-soft mb-4">Nowe hasło musi mieć co najmniej 8 znaków, literę i cyfrę.</p>

                    <?php if ($passwordErrors !== []): ?>
                        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= e(reset($passwordErrors)) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('profile.php')) ?>" novalidate class="needs-validation">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="password">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="current_password">Obecne hasło</label>
                                <div class="input-group has-validation">
                                    <input class="form-control <?= isset($passwordErrors['current_password']) ? 'is-invalid' : '' ?>"
                                           type="password" id="current_password" name="current_password"
                                           autocomplete="current-password" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                            data-password-toggle="current_password" aria-label="Pokaż hasło"><i class="bi bi-eye"></i></button>
                                    <div class="invalid-feedback"><?= e($passwordErrors['current_password'] ?? 'Podaj obecne hasło.') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="new_password">Nowe hasło</label>
                                <div class="input-group has-validation">
                                    <input class="form-control <?= isset($passwordErrors['new_password']) ? 'is-invalid' : '' ?>"
                                           type="password" id="new_password" name="new_password"
                                           autocomplete="new-password" minlength="8" maxlength="72"
                                           pattern="(?=.*[A-Za-z])(?=.*\d).{8,72}"
                                           data-password-strength="#new-password-meter" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                            data-password-toggle="new_password" aria-label="Pokaż hasło"><i class="bi bi-eye"></i></button>
                                    <div class="invalid-feedback"><?= e($passwordErrors['new_password'] ?? 'Min. 8 znaków, litera i cyfra.') ?></div>
                                </div>
                                <div class="password-meter" id="new-password-meter"><span></span></div>
                                <div class="small mt-1" data-strength-label></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="new_password_confirm">Powtórz nowe hasło</label>
                                <input class="form-control <?= isset($passwordErrors['new_password_confirm']) ? 'is-invalid' : '' ?>"
                                       type="password" id="new_password_confirm" name="new_password_confirm"
                                       autocomplete="new-password" data-match="#new_password" required>
                                <div class="invalid-feedback"><?= e($passwordErrors['new_password_confirm'] ?? 'Hasła muszą być identyczne.') ?></div>
                            </div>
                        </div>

                        <button class="btn btn-primary mt-4" type="submit">
                            <i class="bi bi-key me-1"></i> Zmień hasło
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
