<?php
require __DIR__ . '/../../app/bootstrap.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $target = $id > 0 ? $users->find($id) : null;

    if ($target === null) {
        flash('danger', 'Nie znaleziono użytkownika.');
        redirect('admin/users.php');
    }

    if ($action === 'role') {
        $role = (string) ($_POST['role'] ?? '');

        if ($id === auth_id()) {
            flash('warning', 'Nie możesz zmienić roli własnego konta.');
        } elseif (!in_array($role, ['user', 'admin'], true)) {
            flash('danger', 'Nieprawidłowa rola.');
        } elseif ($target['role'] === 'admin' && $role === 'user' && $users->countAdmins() <= 1) {
            flash('danger', 'W systemie musi pozostać co najmniej jeden administrator.');
        } else {
            $users->setRole($id, $role);
            flash('success', 'Rola użytkownika ' . $target['name'] . ' została zmieniona.');
        }

        redirect('admin/users.php');
    }

    if ($action === 'delete') {
        if ($id === auth_id()) {
            flash('warning', 'Nie możesz usunąć własnego konta.');
        } elseif ($target['role'] === 'admin' && $users->countAdmins() <= 1) {
            flash('danger', 'Nie można usunąć ostatniego administratora.');
        } else {
            $users->delete($id);
            flash('success', 'Konto użytkownika ' . $target['name'] . ' zostało usunięte wraz z rezerwacjami.');
        }

        redirect('admin/users.php');
    }

    flash('danger', 'Nieznana operacja.');
    redirect('admin/users.php');
}

$allUsers = $users->all();
$adminsCount = 0;
foreach ($allUsers as $row) {
    if ($row['role'] === 'admin') {
        $adminsCount++;
    }
}

$adminActive = 'users';
$pageTitle = 'Użytkownicy — administracja';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="eyebrow"><i class="bi bi-shield-lock"></i> Administracja</span>
                <h1 class="h2 mt-2 mb-1">Użytkownicy</h1>
                <p class="text-muted-soft mb-0">Przeglądaj konta, nadawaj uprawnienia administratora i usuwaj konta.</p>
            </div>
            <span class="badge badge-soft-primary fs-6 px-3 py-2">
                <?= count($allUsers) ?> kont · <?= $adminsCount ?> admin.
            </span>
        </div>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <span class="result-count">Konta są sortowane od najnowszych.</span>
            <input class="form-control form-control-sm" style="max-width: 280px" type="search"
                   placeholder="Filtruj tabelę…" data-table-filter="#users-table" aria-label="Filtruj użytkowników">
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle" id="users-table">
                    <thead>
                        <tr>
                            <th>Użytkownik</th>
                            <th>Telefon</th>
                            <th>Rezerwacje</th>
                            <th>Rola</th>
                            <th>Konto od</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($allUsers === []): ?>
                        <tr><td colspan="6" class="text-center text-muted-soft py-4">Brak użytkowników.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allUsers as $row): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar"><?= e(initials((string) $row['name'])) ?></span>
                                        <div class="min-w-0">
                                            <div class="fw-semibold">
                                                <?= e($row['name']) ?>
                                                <?php if ((int) $row['id'] === auth_id()): ?>
                                                    <span class="badge badge-soft-primary ms-1">to Ty</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted-soft"><?= e($row['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small text-muted-soft text-nowrap"><?= e($row['phone'] ?: '—') ?></td>
                                <td class="small">
                                    <span class="fw-semibold"><?= (int) $row['reservations_count'] ?></span>
                                    <span class="text-muted-soft">(<?= (int) $row['confirmed_count'] ?> potw.)</span>
                                </td>
                                <td>
                                    <?php if ((int) $row['id'] === auth_id()): ?>
                                        <span class="badge text-bg-primary"><?= $row['role'] === 'admin' ? 'Administrator' : 'Użytkownik' ?></span>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('admin/users.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="role">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <select class="form-select form-select-sm" name="role" data-auto-submit
                                                    aria-label="Rola użytkownika <?= e($row['name']) ?>">
                                                <option value="user" <?= selected($row['role'], 'user') ?>>Użytkownik</option>
                                                <option value="admin" <?= selected($row['role'], 'admin') ?>>Administrator</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted-soft text-nowrap"><?= e(pl_date((string) $row['created_at'])) ?></td>
                                <td class="text-end">
                                    <?php if ((int) $row['id'] !== auth_id()): ?>
                                        <form method="post" action="<?= e(url('admin/users.php')) ?>" class="d-inline"
                                              data-confirm="Usunąć konto „<?= e($row['name']) ?>” wraz ze wszystkimi rezerwacjami?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Usuń konto">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="small text-muted-soft">—</span>
                                    <?php endif; ?>
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
            Usunięcie konta usuwa także jego rezerwacje (klucz obcy <code>ON DELETE CASCADE</code>).
        </p>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
