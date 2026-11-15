<?php
require __DIR__ . '/../app/bootstrap.php';

require_login();

$userId = auth_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $reservation = $id > 0 ? $reservations->find($id) : null;

    if ($reservation === null || (int) $reservation['user_id'] !== $userId) {
        flash('danger', 'Nie znaleziono wskazanej rezerwacji.');
        redirect('reservations.php');
    }

    if ($action === 'cancel') {
        if ($reservation['status'] === 'cancelled') {
            flash('info', 'Ta rezerwacja jest już anulowana.');
        } else {
            $reservations->setStatus($id, 'cancelled');
            flash('success', 'Rezerwacja „' . $reservation['title'] . '” została anulowana.');
        }
        redirect('reservations.php?scope=' . urlencode((string) ($_POST['scope'] ?? 'upcoming')));
    }

    if ($action === 'delete') {
        $reservations->delete($id);
        flash('success', 'Rezerwacja została usunięta z listy.');
        redirect('reservations.php?scope=' . urlencode((string) ($_POST['scope'] ?? 'all')));
    }

    flash('danger', 'Nieznana operacja.');
    redirect('reservations.php');
}

$status = (string) ($_GET['status'] ?? '');
if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
    $status = '';
}

$scope = (string) ($_GET['scope'] ?? 'upcoming');
if (!in_array($scope, ['upcoming', 'past', 'all'], true)) {
    $scope = 'upcoming';
}

$items = $reservations->forUser($userId, $status, $scope);
$counts = $reservations->countsForUser($userId);
$upcomingCount = $reservations->countUpcomingForUser($userId);

$months = ['sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paź', 'lis', 'gru'];
$pageTitle = 'Moje rezerwacje';

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Start</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Moje rezerwacje</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-2">Moje rezerwacje</h1>
                <p class="text-muted-soft mb-0">
                    Zarządzaj terminami, edytuj szczegóły i anuluj rezerwacje, których nie potrzebujesz.
                </p>
            </div>
            <a class="btn btn-primary" href="<?= e(url('rooms.php')) ?>">
                <i class="bi bi-plus-lg me-1"></i> Nowa rezerwacja
            </a>
        </div>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="icon" style="background: var(--brand-light); color: var(--brand)"><i class="bi bi-calendar3"></i></div>
                    <div class="value"><?= $counts['total'] ?></div>
                    <div class="label">wszystkich rezerwacji</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="icon" style="background: rgba(245,158,11,.13); color: #d97706"><i class="bi bi-hourglass-split"></i></div>
                    <div class="value"><?= $counts['pending'] ?></div>
                    <div class="label">oczekujących</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="icon" style="background: rgba(16,185,129,.13); color: #059669"><i class="bi bi-check2-circle"></i></div>
                    <div class="value"><?= $counts['confirmed'] ?></div>
                    <div class="label">potwierdzonych</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat">
                    <div class="icon" style="background: rgba(239,68,68,.12); color: #dc2626"><i class="bi bi-x-circle"></i></div>
                    <div class="value"><?= $counts['cancelled'] ?></div>
                    <div class="label">anulowanych</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <ul class="nav nav-pills gap-1">
                <?php
                $scopes = [
                    'upcoming' => 'Nadchodzące',
                    'past'     => 'Historia',
                    'all'      => 'Wszystkie',
                ];
                foreach ($scopes as $key => $label):
                    $params = ['scope' => $key];
                    if ($status !== '') {
                        $params['status'] = $status;
                    }
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $scope === $key ? 'active' : '' ?>" href="?<?= e(http_build_query($params)) ?>">
                            <?= e($label) ?>
                            <?php if ($key === 'upcoming' && $upcomingCount > 0): ?>
                                <span class="badge text-bg-light ms-1"><?= $upcomingCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form method="get" action="<?= e(url('reservations.php')) ?>" class="d-flex align-items-center gap-2">
                <input type="hidden" name="scope" value="<?= e($scope) ?>">
                <label class="small text-muted-soft text-nowrap" for="status">Status</label>
                <select class="form-select form-select-sm" id="status" name="status" data-auto-submit>
                    <option value="">Wszystkie</option>
                    <option value="pending" <?= selected($status, 'pending') ?>>Oczekujące</option>
                    <option value="confirmed" <?= selected($status, 'confirmed') ?>>Potwierdzone</option>
                    <option value="cancelled" <?= selected($status, 'cancelled') ?>>Anulowane</option>
                </select>
            </form>
        </div>

        <?php if ($items === []): ?>
            <div class="empty-state">
                <i class="bi bi-calendar2-x"></i>
                <h2 class="h5 fw-bold mt-3">Brak rezerwacji w tym widoku</h2>
                <p class="text-muted-soft mb-4">
                    <?= $scope === 'upcoming' ? 'Nie masz zaplanowanych spotkań. Znajdź salę i zarezerwuj pierwszy termin.' : 'Nie znaleziono rezerwacji spełniających wybrane kryteria.' ?>
                </p>
                <a class="btn btn-primary" href="<?= e(url('rooms.php')) ?>">Przeglądaj sale</a>
            </div>
        <?php else: ?>
            <div class="reservation-list">
                <?php foreach ($items as $item): ?>
                    <?php
                    $startTs = strtotime($item['reservation_date'] . ' ' . $item['start_time']);
                    $isFuture = $startTs !== false && $startTs > time();
                    $canEdit = $isFuture && $item['status'] !== 'cancelled' && ($item['status'] === 'pending' || is_admin());
                    $canCancel = $item['status'] !== 'cancelled' && ($isFuture || is_admin());
                    $day = date('d', strtotime((string) $item['reservation_date']));
                    $month = $months[(int) date('n', strtotime((string) $item['reservation_date'])) - 1];
                    ?>
                    <article class="reservation-item">
                        <div class="date-tile">
                            <span class="dt-day"><?= $day ?></span>
                            <span class="dt-month"><?= $month ?></span>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <h2 class="h6 fw-bold mb-1"><?= e($item['title']) ?></h2>
                                    <div class="res-meta">
                                        <span><i class="bi bi-door-open"></i><?= e($item['room_name']) ?></span>
                                        <span><i class="bi bi-geo-alt"></i><?= e($item['room_location']) ?></span>
                                        <span><i class="bi bi-clock"></i><?= e(pl_date((string) $item['reservation_date'])) ?>, <?= e(short_time((string) $item['start_time'])) ?>–<?= e(short_time((string) $item['end_time'])) ?></span>
                                        <span><i class="bi bi-people"></i><?= (int) $item['guests'] ?> os.</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?= e(status_class((string) $item['status'])) ?>"><?= e(status_label((string) $item['status'])) ?></span>
                                    <div class="fw-bold mt-2"><?= e(money($item['total_price'])) ?></div>
                                </div>
                            </div>

                            <?php if (!empty($item['notes'])): ?>
                                <p class="small text-muted-soft mb-0 mt-2">
                                    <i class="bi bi-chat-left-text me-1"></i><?= e(excerpt((string) $item['notes'], 160)) ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('room.php?id=' . (int) $item['room_id'])) ?>">
                                    <i class="bi bi-eye me-1"></i> Szczegóły sali
                                </a>
                                <?php if ($canEdit): ?>
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="<?= e(url('reservation-edit.php?id=' . (int) $item['id'])) ?>">
                                        <i class="bi bi-pencil me-1"></i> Edytuj
                                    </a>
                                <?php endif; ?>
                                <?php if ($canCancel): ?>
                                    <form method="post" action="<?= e(url('reservations.php')) ?>"
                                          data-confirm="Czy na pewno chcesz anulować rezerwację „<?= e($item['title']) ?>”?"
                                          class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="scope" value="<?= e($scope) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-x-circle me-1"></i> Anuluj
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= e(url('reservations.php')) ?>"
                                      data-confirm="Usunięcie rezerwacji jest nieodwracalne. Kontynuować?"
                                      class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="scope" value="<?= e($scope) ?>">
                                    <button class="btn btn-sm btn-link text-danger text-decoration-none" type="submit">
                                        <i class="bi bi-trash3 me-1"></i> Usuń
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
