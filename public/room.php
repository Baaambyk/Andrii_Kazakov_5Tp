<?php
require __DIR__ . '/../app/bootstrap.php';

$roomId = (int) ($_GET['id'] ?? $_POST['room_id'] ?? 0);
$room = $roomId > 0 ? $rooms->find($roomId) : null;

if ($room === null || (!(int) $room['is_active'] && !is_admin())) {
    abort_page(404, 'Nie znaleziono sali', 'Sala o podanym numerze nie istnieje lub nie jest już dostępna.');
}

$errors = [];
$form = [
    'reservation_date' => (string) ($_POST['reservation_date'] ?? date('Y-m-d')),
    'start_time'       => (string) ($_POST['start_time'] ?? '09:00'),
    'end_time'         => (string) ($_POST['end_time'] ?? '11:00'),
    'guests'           => (string) ($_POST['guests'] ?? min(4, (int) $room['capacity'])),
    'title'            => (string) ($_POST['title'] ?? ''),
    'notes'            => (string) ($_POST['notes'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    require_login();
    verify_csrf();

    if (!(int) $room['is_active']) {
        flash('danger', 'Ta sala jest obecnie nieaktywna i nie można jej zarezerwować.');
        redirect('rooms.php');
    }

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
        && $startMinutes < (int) date('G') * 60 + (int) date('i')) {
        $v->add('start_time', 'Wybrana godzina już minęła. Wybierz późniejszy termin.');
    }

    if ($v->passes() && strtotime($form['reservation_date']) !== false) {
        $hours = hours_between($form['start_time'], $form['end_time']);
        $total = round($hours * (float) $room['price_per_hour'], 2);

        try {
            $reservations->create([
                'user_id'          => auth_id(),
                'room_id'          => (int) $room['id'],
                'title'            => $form['title'],
                'reservation_date' => $form['reservation_date'],
                'start_time'       => $form['start_time'],
                'end_time'         => $form['end_time'],
                'guests'           => (int) $form['guests'],
                'notes'            => $form['notes'],
                'total_price'      => $total,
                'status'           => 'pending',
            ]);

            flash('success', 'Rezerwacja „' . $form['title'] . '” została zgłoszona. Czeka na potwierdzenie administratora.');
            redirect('reservations.php');
        } catch (ReservationConflictException $e) {
            $v->add('start_time', 'Ten przedział czasowy jest już zajęty. Wybierz inne godziny.');
        } catch (Throwable $e) {
            $v->add('title', 'Nie udało się zapisać rezerwacji. Spróbuj ponownie.');
        }
    }

    $errors = $v->errors();
}

$date = $form['reservation_date'];
if (strtotime($date) === false || $date < date('Y-m-d')) {
    $date = date('Y-m-d');
}
$busy = $rooms->busySlots((int) $room['id'], $date);
$amenities = array_filter(array_map('trim', explode(',', (string) ($room['equipment'] ?? ''))));
$pageTitle = $room['name'];

require __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Start</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('rooms.php')) ?>">Sale</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($room['name']) ?></li>
        </ol>
    </nav>

    <?php if (!(int) $room['is_active']): ?>
        <div class="alert alert-warning"><i class="bi bi-eye-slash me-2"></i>Ta sala jest nieaktywna — widok podglądu dla administratora.</div>
    <?php endif; ?>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-7 col-xl-8">
            <div class="room-hero-img mb-4">
                <img src="<?= e(room_image($room)) ?>" alt="<?= e($room['name']) ?>">
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h1 class="h2 fw-bold mb-2"><?= e($room['name']) ?></h1>
                    <p class="text-muted-soft mb-0">
                        <i class="bi bi-geo-alt me-1 text-primary"></i><?= e($room['location']) ?>
                    </p>
                </div>
                <div class="text-end">
                    <div class="booking-price"><?= e(money($room['price_per_hour'])) ?></div>
                    <div class="small text-muted-soft">za godzinę</div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="amenity-pill"><i class="bi bi-people text-primary"></i> do <?= (int) $room['capacity'] ?> osób</span>
                <span class="amenity-pill"><i class="bi bi-clock text-primary"></i> 8:00–22:00</span>
                <span class="amenity-pill"><i class="bi bi-camera-video text-primary"></i> wyposażenie multimedialne</span>
                <span class="amenity-pill"><i class="bi bi-wifi text-primary"></i> Wi-Fi w cenie</span>
            </div>

            <h2 class="h5 fw-bold mb-2">O sali</h2>
            <p class="text-muted-soft mb-4" style="line-height: 1.7"><?= nl2br(e($room['description'])) ?></p>

            <?php if ($amenities !== []): ?>
                <h2 class="h5 fw-bold mb-3">Wyposażenie</h2>
                <div class="row g-2 mb-4">
                    <?php foreach ($amenities as $amenity): ?>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 py-2 px-3 rounded-3" style="background: var(--surface); border: 1px solid var(--border)">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <span class="small fw-semibold"><?= e($amenity) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="detail-panel h-100">
                        <i class="bi bi-calendar2-week text-primary fs-5"></i>
                        <h3 class="h6 fw-bold mt-2 mb-1">Elastyczne terminy</h3>
                        <p class="small text-muted-soft mb-0">Rezerwuj od 30 minut do całego dnia.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="detail-panel h-100">
                        <i class="bi bi-cash-coin text-primary fs-5"></i>
                        <h3 class="h6 fw-bold mt-2 mb-1">Przejrzysta cena</h3>
                        <p class="small text-muted-soft mb-0">Płacisz wyłącznie za wynajęte godziny.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="detail-panel h-100">
                        <i class="bi bi-patch-check text-primary fs-5"></i>
                        <h3 class="h6 fw-bold mt-2 mb-1">Szybkie potwierdzenie</h3>
                        <p class="small text-muted-soft mb-0">Administrator potwierdza rezerwację online.</p>
                    </div>
                </div>
            </div>

            <?php if (is_admin()): ?>
                <div class="mt-4">
                    <a class="btn btn-outline-primary btn-sm" href="<?= e(url('admin/room-form.php?id=' . (int) $room['id'])) ?>">
                        <i class="bi bi-pencil-square me-1"></i> Edytuj salę w panelu
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5 col-xl-4" id="rezerwacja">
            <div class="booking-card" style="position: sticky; top: 90px">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h2 class="h5 fw-bold mb-0">Zarezerwuj termin</h2>
                    <span class="badge badge-soft-primary">online</span>
                </div>
                <p class="small text-muted-soft mb-3">Wybrany dzień: <strong>8:00–22:00</strong></p>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-muted-soft">Zajęte godziny</span>
                        <span class="small text-muted-soft">wybrany dzień</span>
                    </div>
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

                <?php if (auth_check()): ?>
                    <form id="booking-form" method="post" action="<?= e(url('room.php?id=' . (int) $room['id'])) ?>"
                          class="needs-validation <?= $errors !== [] ? 'was-validated' : '' ?>"
                          novalidate
                          data-room="<?= (int) $room['id'] ?>"
                          data-price="<?= e((string) $room['price_per_hour']) ?>"
                          data-capacity="<?= (int) $room['capacity'] ?>"
                          data-availability="<?= e(url('api/availability.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label" for="reservation_date">Data</label>
                            <input class="form-control <?= isset($errors['reservation_date']) ? 'is-invalid' : '' ?>"
                                   type="date" id="reservation_date" name="reservation_date"
                                   value="<?= e($form['reservation_date']) ?>" min="<?= date('Y-m-d') ?>" data-min-today required>
                            <div class="invalid-feedback"><?= e($errors['reservation_date'] ?? 'Wybierz datę rezerwacji.') ?></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label" for="start_time">Od</label>
                                <select class="form-select <?= isset($errors['start_time']) ? 'is-invalid' : '' ?>"
                                        id="start_time" name="start_time" required>
                                    <?php foreach (time_options('08:00', '21:30') as $time): ?>
                                        <option value="<?= $time ?>" <?= selected($form['start_time'], $time) ?>><?= $time ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?= e($errors['start_time'] ?? 'Wybierz godzinę rozpoczęcia.') ?></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="end_time">Do</label>
                                <select class="form-select <?= isset($errors['end_time']) ? 'is-invalid' : '' ?>"
                                        id="end_time" name="end_time" required>
                                    <?php foreach (time_options('08:30', '22:00') as $time): ?>
                                        <option value="<?= $time ?>" <?= selected($form['end_time'], $time) ?>><?= $time ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?= e($errors['end_time'] ?? 'Wybierz godzinę zakończenia.') ?></div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-5">
                                <label class="form-label" for="guests">Osoby</label>
                                <input class="form-control <?= isset($errors['guests']) ? 'is-invalid' : '' ?>"
                                       type="number" id="guests" name="guests" min="1" max="<?= (int) $room['capacity'] ?>"
                                       value="<?= e($form['guests']) ?>" required>
                                <div class="invalid-feedback"><?= e($errors['guests'] ?? 'Liczba osób 1–' . (int) $room['capacity'] . '.') ?></div>
                            </div>
                            <div class="col-7">
                                <label class="form-label" for="title">Nazwa spotkania</label>
                                <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                       type="text" id="title" name="title" maxlength="150"
                                       value="<?= e($form['title']) ?>" placeholder="np. Warsztat zespołu" required>
                                <div class="invalid-feedback"><?= e($errors['title'] ?? 'Podaj nazwę spotkania (min. 3 znaki).') ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="notes">Uwagi <span class="text-muted-soft fw-normal">(opcjonalnie)</span></label>
                            <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                                      id="notes" name="notes" rows="2" maxlength="500"
                                      placeholder="Dodatkowe potrzeby, sprzęt, catering…"><?= e($form['notes']) ?></textarea>
                            <div class="invalid-feedback"><?= e($errors['notes'] ?? 'Maksymalnie 500 znaków.') ?></div>
                        </div>

                        <div class="summary-box mb-3">
                            <div class="summary-row"><span class="text-muted-soft">Termin</span><strong data-summary-date>—</strong></div>
                            <div class="summary-row"><span class="text-muted-soft">Godziny</span><strong data-summary-time>—</strong></div>
                            <div class="summary-row"><span class="text-muted-soft">Czas</span><strong data-summary-duration>—</strong></div>
                            <div class="summary-row pt-2 mt-2 border-top"><span class="fw-semibold">Razem</span><strong data-summary-total>—</strong></div>
                        </div>

                        <div class="alert alert-danger py-2 px-3 small" data-booking-error hidden></div>

                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-calendar2-check me-1"></i> Zarezerwuj salę
                        </button>
                        <p class="small text-muted-soft text-center mt-3 mb-0">
                            Rezerwacja wymaga potwierdzenia przez administratora.
                        </p>
                    </form>
                <?php else: ?>
                    <div class="text-center py-3">
                        <div class="feature-icon mx-auto"><i class="bi bi-lock"></i></div>
                        <h3 class="h6 fw-bold">Zaloguj się, aby zarezerwować</h3>
                        <p class="small text-muted-soft">Rezerwacja jest dostępna dla zarejestrowanych użytkowników.</p>
                        <a class="btn btn-primary w-100 mb-2"
                           href="<?= e(url('login.php?next=' . urlencode(url('room.php?id=' . (int) $room['id'])))) ?>">Zaloguj się</a>
                        <a class="btn btn-outline-primary w-100" href="<?= e(url('register.php')) ?>">Utwórz konto</a>
                    </div>
                <?php endif; ?>

                <hr class="my-3">
                <div class="d-flex justify-content-between small text-muted-soft">
                    <span><i class="bi bi-shield-check me-1 text-success"></i>Bezpieczna rezerwacja</span>
                    <span><?= e(money($room['price_per_hour'])) ?> / godz.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
