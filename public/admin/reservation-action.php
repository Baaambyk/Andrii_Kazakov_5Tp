<?php
require __DIR__ . '/../../app/bootstrap.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_page(405, 'Niedozwolona metoda', 'Ta operacja wymaga wysłania formularza.');
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$returnTo = safe_next((string) ($_POST['return'] ?? 'admin/reservations.php'));

$reservation = $id > 0 ? $reservations->find($id) : null;

if ($reservation === null) {
    flash('danger', 'Nie znaleziono rezerwacji.');
    redirect($returnTo);
}

try {
    switch ($action) {
        case 'confirm':
            $reservations->setStatus($id, 'confirmed');
            flash('success', 'Rezerwacja „' . $reservation['title'] . '” została potwierdzona.');
            break;

        case 'cancel':
            $reservations->setStatus($id, 'cancelled');
            flash('success', 'Rezerwacja „' . $reservation['title'] . '” została anulowana.');
            break;

        case 'pending':
            $reservations->setStatus($id, 'pending');
            flash('info', 'Rezerwacja wróciła do statusu oczekującej.');
            break;

        case 'delete':
            $reservations->delete($id);
            flash('success', 'Rezerwacja została trwale usunięta.');
            break;

        default:
            flash('danger', 'Nieznana operacja.');
    }
} catch (Throwable $e) {
    flash('danger', 'Nie udało się wykonać operacji. Spróbuj ponownie.');
}

redirect($returnTo);
