<?php
require __DIR__ . '/../app/bootstrap.php';

require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$reservation = $id > 0 ? $reservations->find($id) : null;

if ($reservation === null || ((int) $reservation['user_id'] !== auth_id() && !is_admin())) {
    abort_page(404, 'Nie znaleziono rezerwacji', 'Rezerwacja nie istnieje lub nie masz do niej dostępu.');
}

if ($reservation['status'] === 'cancelled') {
    flash('warning', 'Anulowanej rezerwacji nie można edytować.');
    redirect('reservations.php?scope=all');
}

if ($reservation['status'] === 'confirmed' && !is_admin()) {
    flash('info', 'Potwierdzona rezerwacja wymaga kontaktu z administratorem w celu zmiany terminu.');
    redirect('reservations.php');
}

$room = $rooms->find((int) $reservation['room_id']);
if ($room === null) {
    abort_page(404, 'Nie znaleziono sali', 'Sala powiązana z rezerwacją nie istnieje.');
}

$form = [
    'reservation_date' => (string) ($_POST['reservation_date'] ?? $reservation['reservation_date']),
    'start_time'       => (string) ($_POST['start_time'] ?? short_time((string) $reservation['start_time'])),
    'end_time'         => (string) ($_POST['end_time'] ?? short_time((string) $reservation['end_time'])),
    'guests'           => (string) ($_POST['guests'] ?? $reservation['guests']),
    'title'            => (string) ($_POST['title'] ?? $reservation['title']),
    'notes'            => (string) ($_POST['notes'] ?? $reservation['notes']),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    verify_csrf();

    $v = new Validator();
    $v->required('title', $form['title'], 'Podaj nazwę spotkania.')
        ->minLength('title', $form['title'], 3, 'Nazwa powinna mieć co najmniej 3 znaki.')
        ->maxLength('title', $form['title'], 150)
        ->required('reservation_date', $form['reservation_date'])
        ->date('reservation_date', $form['reservation_date'])
        ->futureDate('reservation_date', $form['reservation_date'])
        ->time('start_time', $form['start_time'])
        ->time('end_time', $form['end_time'])
        ->integer('guests', $form['guests'], 1, (int) $room['capacity'], 'Liczba osób musi mieścić się w pojemności sali (' . (int) $room['capacity'] . ').')
        ->maxLength('notes', $form['notes'], 500);

    $startMinutes = time_to_minutes($form['start_time']);
    $endMinutes = time_to_minutes($form['end_time']);

    if ($startMinutes !== null && $endMinutes !== null) {
        if ($endMinutes <= $startMinutes) {
            $v->add('end_time', 'Godzina zakończenia musi być późniejsza niż rozpoczęcia.');
        } elseif ($endMinutes - $startMinutes < 30) {
            $v->add('end_time', 'Minimalny czas rezerwacji to 30 minut.');
        }

        if ($startMinutes < 8 * 60 || $endMinutes > 22 * 60) {
            $v->add('start_time', 'Sale są dostępne w godzinach 8:00–22:00.');
        }
    }

    if ($form['reservation_date'] === date('Y-m-d') && $startMinutes !== null
        && $startMinutes < (int) date('G') * 60 + (int) date('i') && !is_admin()) {
        $v->add('start_time', 'Wybrana godzina już minęła. Wybierz późniejszy termin.');
    }

    if ($v->passes()) {
        $hours = hours_between($form['start_time'], $form['end_time']);
        $total = round($hours * (float) $room['price_per_hour'], 2);

        try {
            $reservations->update($id, [
                'room_id'          => (int) $room['id'],
                'title'            => $form['title'],
                'reservation_date' => $form['reservation_date'],
                'start_time'       => $form['start_time'],
                'end_time'         => $form['end_time'],
                'guests'           => (int) $form['guests'],
                'notes'            => $form['notes'],
                'total_price'      => $total,
            ]);

            flash('success', 'Rezerwacja została zaktualizowana.');
            redirect('reservations.php');
        } catch (ReservationConflictException $e) {
            $v->add('start_time', 'Ten przedział czasowy jest już zajęty. Wybierz inne godziny.');
        } catch (Throwable $e) {
            $v->add('title', 'Nie udało się zapisać zmian. Spróbuj ponownie.');
        }
    }

    $errors = $v->errors();
}

$busy = $rooms->busySlots((int) $room['id'], (string) $form['reservation_date'], $id);
$pageTitle = 'Edycja rezerwacji';

require __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Start</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('reservations.php')) ?>">Moje rezerwacje</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edycja</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="detail-panel">
                <h1 class="h3 fw-bold mb-1">Edycja rezerwacji</h1>
                <p class="text-muted-soft mb-4">
                    Zmień szczegóły spotkania. Termin musi pozostać wolny w kalendarzu sali.
                </p>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i> Popraw zaznaczone pola i spróbuj ponownie.
                    </div>
                <?php endif; ?>

                <form id="booking-form" method="post" action="<?= e(url('reservation-edit.php?id=' . $id)) ?>"
                      class="needs-validation <?= $errors !== [] ? 'was-validated' : '' ?>"
                      novalidate
                      data-room="<?= (int) $room['id'] ?>"
                      data-exclude="<?= $id ?>"
                      data-price="<?= e((string) $room['price_per_hour']) ?>"
                      data-capacity="<?= (int) $room['capacity'] ?>"
                      data-availability="<?= e(url('api/availability.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="mb-3">
                        <label class="form-label" for="title">Nazwa spotkania</label>
                        <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               type="text" id="title" name="title" maxlength="150"
                               value="<?= e($form['title']) ?>" required>
                        <div class="invalid-feedback"><?= e($errors['title'] ?? 'Podaj nazwę spotkania.') ?></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="reservation_date">Data</label>
                            <input class="form-control <?= isset($errors['reservation_date']) ? 'is-invalid' : '' ?>"
                                   type="date" id="reservation_date" name="reservation_date"
                                   value="<?= e($form['reservation_date']) ?>" min="<?= date('Y-m-d') ?>" data-min-today required>
                            <div class="invalid-feedback"><?= e($errors['reservation_date'] ?? 'Wybierz datę.') ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="start_time">Od</label>
                            <select class="form-select <?= isset($errors['start_time']) ? 'is-invalid' : '' ?>"
                                    id="start_time" name="start_time" required>
                                <?php foreach (time_options('08:00', '21:30') as $time): ?>
                                    <option value="<?= $time ?>" <?= selected($form['start_time'], $time) ?>><?= $time ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errors['start_time'] ?? 'Wybierz godzinę.') ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="end_time">Do</label>
                            <select class="form-select <?= isset($errors['end_time']) ? 'is-invalid' : '' ?>"
                                    id="end_time" name="end_time" required>
                                <?php foreach (time_options('08:30', '22:00') as $time): ?>
                                    <option value="<?= $time ?>" <?= selected($form['end_time'], $time) ?>><?= $time ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errors['end_time'] ?? 'Wybierz godzinę.') ?></div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="guests">Osoby</label>
                            <input class="form-control <?= isset($errors['guests']) ? 'is-invalid' : '' ?>"
                                   type="number" id="guests" name="guests" min="1" max="<?= (int) $room['capacity'] ?>"
                                   value="<?= e($form['guests']) ?>" required>
                            <div class="invalid-feedback"><?= e($errors['guests'] ?? '1–' . (int) $room['capacity']) ?></div>
                        </div>
                    </div>

                    <div class="mt-3 mb-3">
                        <label class="form-label" for="notes">Uwagi</label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                                  id="notes" name="notes" rows="3" maxlength="500"><?= e($form['notes']) ?></textarea>
                        <div class="invalid-feedback"><?= e($errors['notes'] ?? 'Maksymalnie 500 znaków.') ?></div>
                    </div>

                    <div class="mb-3">
                        <div class="small fw-semibold text-muted-soft mb-2">Zajęte godziny (bez tej rezerwacji)</div>
                        <div class="busy-list" data-busy-slots>
                            <?php if ($busy === []): ?>
                                <span class="time-chip free"><i class="bi bi-check2"></i> Cały dzień wolny</span>
                            <?php else: ?>
                                <?php foreach ($busy as $slot): ?>
                                    <span class="time-chip"><i class="bi bi-lock"></i> <?= e($slot['start']) ?>–<?= e($slot['end']) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="summary-box mb-3">
                        <div class="summary-row"><span class="text-muted-soft">Termin</span><strong data-summary-date>—</strong></div>
                        <div class="summary-row"><span class="text-muted-soft">Godziny</span><strong data-summary-time>—</strong></div>
                        <div class="summary-row"><span class="text-muted-soft">Czas</span><strong data-summary-duration>—</strong></div>
                        <div class="summary-row pt-2 mt-2 border-top"><span class="fw-semibold">Razem</span><strong data-summary-total>—</strong></div>
                    </div>

                    <div class="alert alert-danger py-2 px-3 small" data-booking-error hidden></div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-check2 me-1"></i> Zapisz zmiany
                        </button>
                        <a class="btn btn-outline-secondary" href="<?= e(url('reservations.php')) ?>">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="booking-card">
                <div class="room-media rounded-3 mb-3" style="aspect-ratio: 16/9">
                    <img src="<?= e(room_image($room)) ?>" alt="<?= e($room['name']) ?>">
                </div>
                <h2 class="h5 fw-bold mb-1"><?= e($room['name']) ?></h2>
                <p class="text-muted-soft small mb-3"><i class="bi bi-geo-alt me-1"></i><?= e($room['location']) ?></p>

                <ul class="list-unstyled small mb-3">
                    <li class="d-flex justify-content-between py-1">
                        <span class="text-muted-soft">Stawka godzinowa</span>
                        <strong><?= e(money($room['price_per_hour'])) ?></strong>
                    </li>
                    <li class="d-flex justify-content-between py-1">
                        <span class="text-muted-soft">Pojemność</span>
                        <strong><?= (int) $room['capacity'] ?> osób</strong>
                    </li>
                    <li class="d-flex justify-content-between py-1">
                        <span class="text-muted-soft">Status</span>
                        <span class="badge <?= e(status_class((string) $reservation['status'])) ?>"><?= e(status_label((string) $reservation['status'])) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-1">
                        <span class="text-muted-soft">Utworzono</span>
                        <strong><?= e(pl_date((string) $reservation['created_at'])) ?></strong>
                    </li>
                </ul>

                <?php if (is_admin()): ?>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-shield-lock me-1"></i>
                        Edytujesz rezerwację użytkownika <strong><?= e($reservation['user_name']) ?></strong>.
                    </div>
                <?php else: ?>
                    <p class="small text-muted-soft mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Po zmianie terminu rezerwacja pozostaje w statusie „oczekuje” do potwierdzenia przez administratora.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
