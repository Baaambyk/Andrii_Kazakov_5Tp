<?php
/**
 * Wspólne zakładki panelu administratora.
 * Oczekuje zmiennej $adminActive (pulpit|rooms|reservations|users).
 */
$adminActive = $adminActive ?? 'pulpit';
$adminTabs = [
    'pulpit'       => ['label' => 'Pulpit', 'icon' => 'speedometer2', 'file' => 'admin/index.php'],
    'rooms'        => ['label' => 'Sale', 'icon' => 'door-open', 'file' => 'admin/rooms.php'],
    'reservations' => ['label' => 'Rezerwacje', 'icon' => 'calendar3', 'file' => 'admin/reservations.php'],
    'users'        => ['label' => 'Użytkownicy', 'icon' => 'people', 'file' => 'admin/users.php'],
];
?>
<div class="admin-tabs">
    <?php foreach ($adminTabs as $key => $tab): ?>
        <a class="<?= $adminActive === $key ? 'active' : '' ?>" href="<?= e(url($tab['file'])) ?>">
            <i class="bi bi-<?= e($tab['icon']) ?> me-1"></i><?= e($tab['label']) ?>
        </a>
    <?php endforeach; ?>
    <a class="ms-auto" href="<?= e(url('index.php')) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Przejdź do serwisu</a>
</div>
