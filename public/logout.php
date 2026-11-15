<?php
require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_page(405, 'Niedozwolona metoda', 'Wylogowanie wymaga wysłania formularza.');
}

verify_csrf();
logout_user();
flash('success', 'Zostałeś wylogowany. Do zobaczenia!');
redirect('index.php');
