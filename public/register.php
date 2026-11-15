<?php
require __DIR__ . '/../app/bootstrap.php';

require_guest();

$errors = [];
$form = [
    'name'  => '',
    'email' => '',
    'phone' => '',
];
$next = safe_next((string) ($_GET['next'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $form['name'] = trim((string) ($_POST['name'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    $v = new Validator();
    $v->required('name', $form['name'], 'Podaj imię i nazwisko.')
        ->minLength('name', $form['name'], 3, 'Imię i nazwisko muszą mieć co najmniej 3 znaki.')
        ->maxLength('name', $form['name'], 80)
        ->required('email', $form['email'], 'Podaj adres e-mail.')
        ->email('email', $form['email'])
        ->maxLength('email', $form['email'], 190)
        ->required('password', $password, 'Podaj hasło.')
        ->minLength('password', $password, 8, 'Hasło musi mieć co najmniej 8 znaków.')
        ->maxLength('password', $password, 72, 'Hasło może mieć maksymalnie 72 znaki.')
        ->required('password_confirm', $passwordConfirm, 'Powtórz hasło.')
        ->maxLength('phone', $form['phone'], 30);

    if ($password !== '' && (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password))) {
        $v->add('password', 'Hasło musi zawierać przynajmniej jedną literę i jedną cyfrę.');
    }

    if ($password !== '' && $passwordConfirm !== '' && $password !== $passwordConfirm) {
        $v->add('password_confirm', 'Hasła nie są identyczne.');
    }

    if ($form['phone'] !== '' && !preg_match('/^[0-9+\s()-]{6,30}$/', $form['phone'])) {
        $v->add('phone', 'Podaj poprawny numer telefonu.');
    }

    if (!isset($_POST['terms'])) {
        $v->add('terms', 'Musisz zaakceptować regulamin, aby utworzyć konto.');
    }

    if ($v->passes() && $users->findByEmail($form['email']) !== null) {
        $v->add('email', 'Konto z tym adresem e-mail już istnieje.');
    }

    if ($v->passes()) {
        try {
            $userId = $users->create(
                $form['name'],
                $form['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $form['phone'] !== '' ? $form['phone'] : null
            );
            $user = $users->find($userId);

            if ($user !== null) {
                login_user($user);
                flash('success', 'Konto zostało utworzone. Witaj, ' . explode(' ', $form['name'])[0] . '!');
                redirect($next !== 'reservations.php' ? $next : 'rooms.php');
            }
        } catch (PDOException $e) {
            $v->add('email', 'Nie udało się utworzyć konta — adres e-mail jest już zajęty.');
        }
    }

    $errors = $v->errors();
}

$pageTitle = 'Rejestracja';
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
                    <h2 class="h3">Zacznij planować lepiej</h2>
                    <p style="color: rgba(255,255,255,.75)">
                        Jedno konto, wszystkie sale i terminy w jednym miejscu.
                        Rezerwacja zajmuje mniej niż minutę.
                    </p>
                    <ul class="mt-auto">
                        <li><i class="bi bi-check-circle-fill"></i> Natychmiastowy dostęp do kalendarza sal</li>
                        <li><i class="bi bi-check-circle-fill"></i> Historia i edycja rezerwacji</li>
                        <li><i class="bi bi-check-circle-fill"></i> Hasła hashowane algorytmem bcrypt</li>
                        <li><i class="bi bi-check-circle-fill"></i> Ochrona formularzy tokenami CSRF</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-9 col-lg-6">
                <div class="auth-card">
                    <div class="mb-4">
                        <span class="eyebrow"><i class="bi bi-person-plus"></i> Nowe konto</span>
                        <h1 class="h3 fw-bold mt-2 mb-2">Zarejestruj się</h1>
                        <p class="text-muted-soft mb-0">Wypełnij formularz — zajmie to mniej niż minutę.</p>
                    </div>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert-danger py-2 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Formularz zawiera błędy. Popraw zaznaczone pola.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('register.php' . ($next !== 'reservations.php' ? '?next=' . urlencode($next) : ''))) ?>"
                          novalidate class="needs-validation">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="name">Imię i nazwisko</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   type="text" id="name" name="name" value="<?= e($form['name']) ?>"
                                   placeholder="Jan Kowalski" autocomplete="name" maxlength="80" required>
                            <div class="invalid-feedback"><?= e($errors['name'] ?? 'Podaj imię i nazwisko.') ?></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="email">Adres e-mail</label>
                                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       type="email" id="email" name="email" value="<?= e($form['email']) ?>"
                                       placeholder="twoj@email.pl" autocomplete="email" maxlength="190" required>
                                <div class="invalid-feedback"><?= e($errors['email'] ?? 'Podaj poprawny adres e-mail.') ?></div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="phone">Telefon <span class="text-muted-soft fw-normal">(opcjonalnie)</span></label>
                                <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                       type="tel" id="phone" name="phone" value="<?= e($form['phone']) ?>"
                                       placeholder="+48 600 000 000" autocomplete="tel" maxlength="30">
                                <div class="invalid-feedback"><?= e($errors['phone'] ?? 'Podaj poprawny numer telefonu.') ?></div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label" for="password">Hasło</label>
                            <div class="input-group has-validation">
                                <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       type="password" id="password" name="password" placeholder="min. 8 znaków"
                                       autocomplete="new-password" minlength="8" maxlength="72" required
                                       pattern="(?=.*[A-Za-z])(?=.*\d).{8,72}"
                                       data-password-strength="#password-meter">
                                <button class="btn btn-outline-secondary" type="button"
                                        data-password-toggle="password" aria-label="Pokaż hasło">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="invalid-feedback">
                                    <?= e($errors['password'] ?? 'Hasło musi mieć min. 8 znaków, literę i cyfrę.') ?>
                                </div>
                            </div>
                            <div class="password-meter" id="password-meter"><span></span></div>
                            <div class="small mt-1" data-strength-label></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password_confirm">Powtórz hasło</label>
                            <input class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                                   type="password" id="password_confirm" name="password_confirm"
                                   placeholder="wpisz hasło ponownie" autocomplete="new-password" required
                                   data-match="#password">
                            <div class="invalid-feedback"><?= e($errors['password_confirm'] ?? 'Hasła muszą być identyczne.') ?></div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>"
                                   type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label small" for="terms">
                                Akceptuję <a href="#" class="fw-semibold">regulamin</a> oraz
                                <a href="#" class="fw-semibold">politykę prywatności</a> serwisu BookIt.
                            </label>
                            <div class="invalid-feedback"><?= e($errors['terms'] ?? 'Zgoda jest wymagana.') ?></div>
                        </div>

                        <button class="btn btn-primary btn-lg w-100" type="submit">
                            <i class="bi bi-person-check me-1"></i> Utwórz konto
                        </button>
                    </form>

                    <p class="text-center text-muted-soft small mt-4 mb-0">
                        Masz już konto?
                        <a href="<?= e(url('login.php' . ($next !== 'reservations.php' ? '?next=' . urlencode($next) : ''))) ?>"
                           class="fw-semibold">Zaloguj się</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
