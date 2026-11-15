<?php
require __DIR__ . '/../../app/bootstrap.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    $room = $id > 0 ? $rooms->find($id) : null;

    if ($room === null) {
        flash('danger', 'Nie znaleziono sali.');
        redirect('admin/rooms.php');
    }

    try {
        $rooms->delete($id);

        if (str_starts_with((string) $room['image'], 'uploads/rooms/')) {
            $file = APP_ROOT . '/public/' . $room['image'];
            if (is_file($file)) {
                @unlink($file);
            }
        }

        flash('success', 'Sala „' . $room['name'] . '” została usunięta.');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            flash('danger', 'Nie można usunąć sali, która ma przypisane rezerwacje. Najpierw usuń lub przenieś rezerwacje.');
        } else {
            throw $e;
        }
    }

    redirect('admin/rooms.php');
}

$allRooms = $rooms->all();
$adminActive = 'rooms';
$pageTitle = 'Zarządzanie salami';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="eyebrow"><i class="bi bi-shield-lock"></i> Administracja</span>
                <h1 class="h2 mt-2 mb-1">Sale</h1>
                <p class="text-muted-soft mb-0">Dodawaj, edytuj i dezaktywuj przestrzenie dostępne w serwisie.</p>
            </div>
            <a class="btn btn-primary" href="<?= e(url('admin/room-form.php')) ?>">
                <i class="bi bi-plus-lg me-1"></i> Dodaj salę
            </a>
        </div>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <span class="result-count"><strong><?= count($allRooms) ?></strong> sal w systemie</span>
            <input class="form-control form-control-sm" style="max-width: 280px" type="search"
                   placeholder="Filtruj tabelę…" data-table-filter="#rooms-table" aria-label="Filtruj sale">
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle" id="rooms-table">
                    <thead>
                        <tr>
                            <th>Sala</th>
                            <th>Pojemność</th>
                            <th>Cena / godz.</th>
                            <th>Status</th>
                            <th>Dodano</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($allRooms === []): ?>
                        <tr><td colspan="6" class="text-center text-muted-soft py-4">Brak sal. Dodaj pierwszą salę.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allRooms as $room): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img class="thumb" src="<?= e(room_image($room)) ?>" alt="<?= e($room['name']) ?>">
                                        <div class="min-w-0">
                                            <div class="fw-semibold"><?= e($room['name']) ?></div>
                                            <div class="small text-muted-soft"><?= e($room['location']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-nowrap"><i class="bi bi-people me-1 text-muted-soft"></i><?= (int) $room['capacity'] ?></td>
                                <td class="text-nowrap fw-semibold"><?= e(money($room['price_per_hour'])) ?></td>
                                <td>
                                    <?php if ((int) $room['is_active']): ?>
                                        <span class="badge text-bg-success">Aktywna</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Nieaktywna</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted-soft text-nowrap"><?= e(pl_date((string) $room['created_at'])) ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('room.php?id=' . (int) $room['id'])) ?>"
                                       title="Podgląd"><i class="bi bi-eye"></i></a>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/room-form.php?id=' . (int) $room['id'])) ?>"
                                       title="Edytuj"><i class="bi bi-pencil"></i></a>
                                    <form method="post" action="<?= e(url('admin/rooms.php')) ?>" class="d-inline"
                                          data-confirm="Usunąć salę „<?= e($room['name']) ?>”? Tej operacji nie można cofnąć.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $room['id'] ?>">
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

        <p class="small text-muted-soft mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Usunięcie sali z rezerwacjami jest zablokowane przez klucz obcy. Zamiast usuwać, możesz ją dezaktywować.
        </p>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
