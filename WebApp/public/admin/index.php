<?php
require __DIR__ . '/../../app/bootstrap.php';

require_admin();

$stats = $reservations->stats();
$roomsCount = $rooms->countActive();
$usersCount = $users->count();
$todayList = $reservations->today();
$recent = $reservations->recent(6);
$topRooms = $reservations->topRooms(5);
$maxTop = 0;
foreach ($topRooms as $top) {
    $maxTop = max($maxTop, (int) $top['reservations_count']);
}

$adminActive = 'pulpit';
$pageTitle = 'Panel administratora';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="eyebrow"><i class="bi bi-shield-lock"></i> Administracja</span>
                <h1 class="h2 mt-2 mb-1">Pulpit</h1>
                <p class="text-muted-soft mb-0">Przegląd rezerwacji, sal i użytkowników systemu BookIt.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= e(url('admin/room-form.php')) ?>">
                    <i class="bi bi-plus-lg me-1"></i> Dodaj salę
                </a>
                <a class="btn btn-primary" href="<?= e(url('admin/reservations.php?status=pending')) ?>">
                    <i class="bi bi-hourglass-split me-1"></i> Oczekujące (<?= (int) $stats['pending'] ?>)
                </a>
            </div>
        </div>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="admin-stat">
                    <div class="icon" style="background: var(--brand-light); color: var(--brand)"><i class="bi bi-calendar3"></i></div>
                    <div class="value"><?= (int) $stats['total'] ?></div>
                    <div class="label">wszystkich rezerwacji</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="admin-stat">
                    <div class="icon" style="background: rgba(245,158,11,.13); color: #d97706"><i class="bi bi-hourglass-split"></i></div>
                    <div class="value"><?= (int) $stats['pending'] ?></div>
                    <div class="label">oczekuje na potwierdzenie</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="admin-stat">
                    <div class="icon" style="background: rgba(16,185,129,.13); color: #059669"><i class="bi bi-check2-circle"></i></div>
                    <div class="value"><?= (int) $stats['confirmed'] ?></div>
                    <div class="label">potwierdzonych rezerwacji</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="admin-stat">
                    <div class="icon" style="background: rgba(14,165,233,.12); color: #0284c7"><i class="bi bi-cash-stack"></i></div>
                    <div class="value"><?= e(money($stats['revenue'])) ?></div>
                    <div class="label">przychód z potwierdzonych</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="table-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-bottom">
                        <div>
                            <h2 class="h6 fw-bold mb-0">Ostatnie rezerwacje</h2>
                            <span class="small text-muted-soft">Najnowsze zgłoszenia w systemie</span>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/reservations.php')) ?>">Zobacz wszystkie</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Spotkanie</th>
                                    <th>Klient</th>
                                    <th>Termin</th>
                                    <th>Status</th>
                                    <th class="text-end">Kwota</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($recent === []): ?>
                                <tr><td colspan="5" class="text-center text-muted-soft py-4">Brak rezerwacji.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e(excerpt((string) $item['title'], 34)) ?></div>
                                            <div class="small text-muted-soft"><?= e($item['room_name']) ?></div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold"><?= e($item['user_name']) ?></div>
                                            <div class="small text-muted-soft"><?= e($item['user_email']) ?></div>
                                        </td>
                                        <td class="small text-nowrap">
                                            <?= e(pl_date((string) $item['reservation_date'])) ?><br>
                                            <span class="text-muted-soft"><?= e(short_time((string) $item['start_time'])) ?>–<?= e(short_time((string) $item['end_time'])) ?></span>
                                        </td>
                                        <td><span class="badge <?= e(status_class((string) $item['status'])) ?>"><?= e(status_label((string) $item['status'])) ?></span></td>
                                        <td class="text-end fw-semibold text-nowrap"><?= e(money($item['total_price'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="detail-panel h-100">
                            <h2 class="h6 fw-bold mb-3"><i class="bi bi-building text-primary me-1"></i> Zasoby</h2>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted-soft">Aktywne sale</span><strong><?= $roomsCount ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted-soft">Użytkownicy</span><strong><?= $usersCount ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span class="text-muted-soft">Rezerwacje dzisiaj</span><strong><?= (int) $stats['today'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-2 small">
                                <span class="text-muted-soft">Anulowane</span><strong><?= (int) $stats['cancelled'] ?></strong>
                            </div>
                            <a class="btn btn-sm btn-outline-primary mt-3" href="<?= e(url('admin/rooms.php')) ?>">
                                Zarządzaj salami
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-panel h-100">
                            <h2 class="h6 fw-bold mb-3"><i class="bi bi-bar-chart text-primary me-1"></i> Najpopularniejsze sale</h2>
                            <?php if ($topRooms === []): ?>
                                <p class="small text-muted-soft mb-0">Brak danych.</p>
                            <?php else: ?>
                                <?php foreach ($topRooms as $top): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="fw-semibold text-truncate pe-2"><?= e($top['name']) ?></span>
                                            <span class="text-muted-soft text-nowrap"><?= (int) $top['reservations_count'] ?></span>
                                        </div>
                                        <div class="progress" style="height: 6px">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: <?= $maxTop > 0 ? (int) round(((int) $top['reservations_count'] / $maxTop) * 100) : 0 ?>%"
                                                 aria-valuenow="<?= (int) $top['reservations_count'] ?>" aria-valuemin="0" aria-valuemax="<?= $maxTop ?>"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="detail-panel h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-calendar-day text-primary me-1"></i> Dzisiaj</h2>
                        <span class="badge badge-soft-primary"><?= e(pl_date(date('Y-m-d'))) ?></span>
                    </div>

                    <?php if ($todayList === []): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-cup-hot fs-2 text-muted-soft"></i>
                            <p class="small text-muted-soft mb-0 mt-2">Dziś nie ma zaplanowanych spotkań.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todayList as $item): ?>
                            <div class="d-flex gap-2 py-3 border-bottom">
                                <div class="text-center" style="min-width: 54px">
                                    <div class="fw-bold"><?= e(short_time((string) $item['start_time'])) ?></div>
                                    <div class="small text-muted-soft"><?= e(short_time((string) $item['end_time'])) ?></div>
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-semibold small text-truncate"><?= e($item['title']) ?></div>
                                    <div class="small text-muted-soft text-truncate"><?= e($item['room_name']) ?></div>
                                    <div class="small text-muted-soft"><?= e($item['user_name']) ?></div>
                                </div>
                                <span class="badge <?= e(status_class((string) $item['status'])) ?> align-self-start ms-auto"><?= e(status_label((string) $item['status'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <a class="btn btn-primary w-100 mt-3" href="<?= e(url('admin/reservations.php')) ?>">
                        <i class="bi bi-calendar3 me-1"></i> Wszystkie rezerwacje
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
