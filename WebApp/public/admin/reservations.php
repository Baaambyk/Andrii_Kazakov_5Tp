<?php
require __DIR__ . '/../../app/bootstrap.php';

require_admin();

$filters = [
    'q'       => trim((string) ($_GET['q'] ?? '')),
    'status'  => (string) ($_GET['status'] ?? ''),
    'room_id' => (int) ($_GET['room_id'] ?? 0),
];

$pagination = $reservations->adminPaginate($filters, max(1, (int) ($_GET['page'] ?? 1)), 12);
$allRooms = $rooms->all();
$queryString = http_build_query(array_filter([
    'q'       => $filters['q'],
    'status'  => $filters['status'],
    'room_id' => $filters['room_id'] > 0 ? $filters['room_id'] : '',
], static fn ($v) => $v !== '' && $v !== null));
$returnPath = 'admin/reservations.php' . ($queryString !== '' ? '?' . $queryString : '');

$adminActive = 'reservations';
$pageTitle = 'Rezerwacje — administracja';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="eyebrow"><i class="bi bi-shield-lock"></i> Administracja</span>
                <h1 class="h2 mt-2 mb-1">Rezerwacje</h1>
                <p class="text-muted-soft mb-0">Potwierdzaj, anuluj i usuwaj rezerwacje wszystkich użytkowników.</p>
            </div>
            <span class="badge badge-soft-primary fs-6 px-3 py-2"><?= (int) $pagination['total'] ?> rezerwacji</span>
        </div>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

        <form class="filter-bar mb-4" method="get" action="<?= e(url('admin/reservations.php')) ?>">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label" for="q">Szukaj</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input class="form-control" id="q" type="search" name="q" value="<?= e($filters['q']) ?>"
                               placeholder="Tytuł, klient, e-mail, sala…">
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" data-auto-submit>
                        <option value="">Wszystkie</option>
                        <option value="pending" <?= selected($filters['status'], 'pending') ?>>Oczekujące</option>
                        <option value="confirmed" <?= selected($filters['status'], 'confirmed') ?>>Potwierdzone</option>
                        <option value="cancelled" <?= selected($filters['status'], 'cancelled') ?>>Anulowane</option>
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label" for="room_id">Sala</label>
                    <select class="form-select" id="room_id" name="room_id" data-auto-submit>
                        <option value="">Wszystkie sale</option>
                        <?php foreach ($allRooms as $room): ?>
                            <option value="<?= (int) $room['id'] ?>" <?= selected($filters['room_id'], $room['id']) ?>>
                                <?= e($room['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i> Filtruj</button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('admin/reservations.php')) ?>"
                       title="Wyczyść filtry" aria-label="Wyczyść filtry"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </div>
        </form>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Spotkanie / klient</th>
                            <th>Sala</th>
                            <th>Termin</th>
                            <th>Status</th>
                            <th class="text-end">Kwota</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($pagination['items'] === []): ?>
                        <tr><td colspan="7" class="text-center text-muted-soft py-5">
                            <i class="bi bi-calendar-x fs-4 d-block mb-2"></i>Brak rezerwacji dla wybranych filtrów.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($pagination['items'] as $item): ?>
                            <tr>
                                <td class="text-muted-soft small">#<?= (int) $item['id'] ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e(excerpt((string) $item['title'], 42)) ?></div>
                                    <div class="small text-muted-soft">
                                        <?= e($item['user_name']) ?> · <?= e($item['user_email']) ?>
                                    </div>
                                    <?php if (!empty($item['notes'])): ?>
                                        <div class="small text-muted-soft fst-italic">„<?= e(excerpt((string) $item['notes'], 60)) ?>”</div>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <div class="fw-semibold"><?= e($item['room_name']) ?></div>
                                    <div class="text-muted-soft"><?= e($item['room_location']) ?></div>
                                </td>
                                <td class="small text-nowrap">
                                    <div class="fw-semibold"><?= e(pl_date((string) $item['reservation_date'], true)) ?></div>
                                    <div class="text-muted-soft"><?= e(short_time((string) $item['start_time'])) ?>–<?= e(short_time((string) $item['end_time'])) ?> · <?= (int) $item['guests'] ?> os.</div>
                                </td>
                                <td>
                                    <span class="badge <?= e(status_class((string) $item['status'])) ?>"><?= e(status_label((string) $item['status'])) ?></span>
                                </td>
                                <td class="text-end fw-semibold text-nowrap"><?= e(money($item['total_price'])) ?></td>
                                <td class="text-end text-nowrap">
                                    <?php if ($item['status'] === 'pending'): ?>
                                        <form method="post" action="<?= e(url('admin/reservation-action.php')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="confirm">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <input type="hidden" name="return" value="<?= e($returnPath) ?>">
                                            <button class="btn btn-sm btn-outline-success" type="submit" title="Potwierdź">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($item['status'] !== 'cancelled'): ?>
                                        <form method="post" action="<?= e(url('admin/reservation-action.php')) ?>" class="d-inline"
                                              data-confirm="Anulować rezerwację „<?= e($item['title']) ?>”?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <input type="hidden" name="return" value="<?= e($returnPath) ?>">
                                            <button class="btn btn-sm btn-outline-warning" type="submit" title="Anuluj">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('admin/reservation-action.php')) ?>" class="d-inline"
                                          data-confirm="Trwale usunąć rezerwację „<?= e($item['title']) ?>”?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="return" value="<?= e($returnPath) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Usuń">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($pagination['items'] !== []): ?>
            <div class="d-flex justify-content-between mt-3">
                <span class="result-count">Strona <?= (int) $pagination['page'] ?> z <?= (int) $pagination['pages'] ?></span>
                <span class="result-count"><?= (int) $pagination['total'] ?> wyników</span>
            </div>
        <?php endif; ?>

        <?php require __DIR__ . '/../includes/pagination.php'; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
