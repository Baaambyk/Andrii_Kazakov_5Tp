<?php
require __DIR__ . '/../../app/bootstrap.php';

require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$room = $id > 0 ? $rooms->find($id) : null;

if ($id > 0 && $room === null) {
    abort_page(404, 'Nie znaleziono sali', 'Sala o podanym numerze nie istnieje.');
}

$form = [
    'name'           => (string) ($_POST['name'] ?? $room['name'] ?? ''),
    'location'       => (string) ($_POST['location'] ?? $room['location'] ?? ''),
    'capacity'       => (string) ($_POST['capacity'] ?? $room['capacity'] ?? '10'),
    'price_per_hour' => (string) ($_POST['price_per_hour'] ?? $room['price_per_hour'] ?? '100'),
    'description'    => (string) ($_POST['description'] ?? $room['description'] ?? ''),
    'equipment'      => (string) ($_POST['equipment'] ?? $room['equipment'] ?? ''),
    'is_active'      => $_SERVER['REQUEST_METHOD'] === 'POST'
        ? (isset($_POST['is_active']) ? 1 : 0)
        : (int) ($room['is_active'] ?? 1),
];

$errors = [];
$imageValue = $room['image'] ?? null;
$newUploadPath = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    verify_csrf();

    $v = new Validator();
    $v->required('name', $form['name'], 'Podaj nazwę sali.')
        ->minLength('name', $form['name'], 3, 'Nazwa musi mieć co najmniej 3 znaki.')
        ->maxLength('name', $form['name'], 120)
        ->required('location', $form['location'], 'Podaj lokalizację.')
        ->minLength('location', $form['location'], 3, 'Lokalizacja musi mieć co najmniej 3 znaki.')
        ->maxLength('location', $form['location'], 120)
        ->integer('capacity', $form['capacity'], 1, 1000)
        ->decimal('price_per_hour', $form['price_per_hour'], 0, 100000)
        ->required('description', $form['description'], 'Dodaj opis sali.')
        ->minLength('description', $form['description'], 20, 'Opis powinien mieć co najmniej 20 znaków.')
        ->maxLength('description', $form['description'], 5000)
        ->maxLength('equipment', $form['equipment'], 255);

    // --- Obsługa zdjęcia ------------------------------------------------
    $hasUpload = isset($_FILES['image']) && (int) $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($hasUpload) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $v->add('image', 'Nie udało się wgrać pliku. Kod błędu: ' . (int) $file['error'] . '.');
        } elseif ((int) $file['size'] > (int) $config['app']['max_upload']) {
            $v->add('image', 'Maksymalny rozmiar zdjęcia to 2 MB.');
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file((string) $file['tmp_name']);

            if (!isset($allowed[$mime])) {
                $v->add('image', 'Dozwolone formaty zdjęć: JPG, PNG, WEBP.');
            } elseif (@getimagesize((string) $file['tmp_name']) === false) {
                $v->add('image', 'Wgrany plik nie jest poprawnym obrazkiem.');
            } elseif ($v->passes()) {
                $uploadDir = (string) $config['app']['upload_dir'];
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                    $v->add('image', 'Brak możliwości zapisania pliku na serwerze.');
                } else {
                    $fileName = 'room-' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                    if (move_uploaded_file((string) $file['tmp_name'], $uploadDir . '/' . $fileName)) {
                        $imageValue = $config['app']['upload_url'] . '/' . $fileName;
                        $newUploadPath = APP_ROOT . '/public/' . $imageValue;
                    } else {
                        $v->add('image', 'Nie udało się zapisać zdjęcia.');
                    }
                }
            }
        }
    } elseif (isset($_POST['remove_image'])) {
        $imageValue = null;
    }

    if ($v->passes()) {
        $data = [
            'name'           => $form['name'],
            'location'       => $form['location'],
            'capacity'       => (int) $form['capacity'],
            'price_per_hour' => (float) $form['price_per_hour'],
            'description'    => $form['description'],
            'equipment'      => $form['equipment'],
            'image'          => $imageValue,
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ];

        try {
            if ($room !== null) {
                $rooms->update((int) $room['id'], $data);
                $savedId = (int) $room['id'];
                flash('success', 'Sala „' . $form['name'] . '” została zaktualizowana.');
            } else {
                $savedId = $rooms->create($data);
                flash('success', 'Sala „' . $form['name'] . '” została dodana.');
            }

            // Usuwamy stary plik tylko wtedy, gdy został zastąpiony lub usunięty.
            $oldImage = (string) ($room['image'] ?? '');
            if ($oldImage !== '' && $oldImage !== $imageValue && str_starts_with($oldImage, 'uploads/rooms/')) {
                $oldPath = APP_ROOT . '/public/' . $oldImage;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            redirect('admin/rooms.php');
        } catch (Throwable $e) {
            $v->add('name', 'Nie udało się zapisać sali. Spróbuj ponownie.');
        }
    }

    if ($newUploadPath !== null && $v->fails()) {
        @unlink($newUploadPath);
        $imageValue = $room['image'] ?? null;
    }

    $errors = $v->errors();
}

$pageTitle = $room !== null ? 'Edytuj salę' : 'Dodaj salę';
$adminActive = 'rooms';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(url('admin/index.php')) ?>">Panel</a></li>
                <li class="breadcrumb-item"><a href="<?= e(url('admin/rooms.php')) ?>">Sale</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $room !== null ? 'Edycja' : 'Nowa sala' ?></li>
            </ol>
        </nav>
        <h1 class="h2 mb-1"><?= $room !== null ? 'Edytuj salę' : 'Dodaj nową salę' ?></h1>
        <p class="text-muted-soft mb-0">Uzupełnij dane, które zobaczą użytkownicy w katalogu sal.</p>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>Popraw zaznaczone pola — formularz zawiera błędy.
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data"
              action="<?= e(url('admin/room-form.php' . ($room !== null ? '?id=' . (int) $room['id'] : ''))) ?>"
              novalidate class="needs-validation">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($room !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $room['id'] ?>">
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="detail-panel">
                        <h2 class="h5 fw-bold mb-4">Podstawowe informacje</h2>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="name">Nazwa sali</label>
                                <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                       type="text" id="name" name="name" maxlength="120"
                                       value="<?= e($form['name']) ?>" placeholder="np. Sala Horizon" required>
                                <div class="invalid-feedback"><?= e($errors['name'] ?? 'Podaj nazwę sali.') ?></div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="location">Lokalizacja</label>
                                <input class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>"
                                       type="text" id="location" name="location" maxlength="120"
                                       value="<?= e($form['location']) ?>" placeholder="Miasto, ulica" required>
                                <div class="invalid-feedback"><?= e($errors['location'] ?? 'Podaj lokalizację.') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="capacity">Pojemność (osoby)</label>
                                <input class="form-control <?= isset($errors['capacity']) ? 'is-invalid' : '' ?>"
                                       type="number" id="capacity" name="capacity" min="1" max="1000"
                                       value="<?= e($form['capacity']) ?>" required>
                                <div class="invalid-feedback"><?= e($errors['capacity'] ?? 'Podaj liczbę od 1 do 1000.') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="price_per_hour">Cena za godzinę (zł)</label>
                                <div class="input-group has-validation">
                                    <input class="form-control <?= isset($errors['price_per_hour']) ? 'is-invalid' : '' ?>"
                                           type="number" id="price_per_hour" name="price_per_hour" min="0" max="100000" step="0.01"
                                           value="<?= e($form['price_per_hour']) ?>" required>
                                    <span class="input-group-text">zł</span>
                                    <div class="invalid-feedback"><?= e($errors['price_per_hour'] ?? 'Podaj poprawną cenę.') ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Opis</label>
                                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                          id="description" name="description" rows="5" maxlength="5000"
                                          placeholder="Opisz charakter sali, widok, atmosferę…" required><?= e($form['description']) ?></textarea>
                                <div class="invalid-feedback"><?= e($errors['description'] ?? 'Opis musi mieć co najmniej 20 znaków.') ?></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="equipment">Wyposażenie</label>
                                <input class="form-control <?= isset($errors['equipment']) ? 'is-invalid' : '' ?>"
                                       type="text" id="equipment" name="equipment" maxlength="255"
                                       value="<?= e($form['equipment']) ?>"
                                       placeholder="Rzutnik 4K, tablica, Wi-Fi (oddziel przecinkami)">
                                <div class="form-text">Elementy oddzielone przecinkami będą wyświetlane jako osobne udogodnienia.</div>
                                <div class="invalid-feedback"><?= e($errors['equipment'] ?? 'Maksymalnie 255 znaków.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="detail-panel mb-4">
                        <h2 class="h6 fw-bold mb-3">Zdjęcie sali</h2>
                        <div class="room-media rounded-3 mb-3" style="aspect-ratio: 16/10">
                            <img id="image-preview" src="<?= e($imageValue !== null ? (preg_match('#^https?://#i', $imageValue) ? $imageValue : url($imageValue)) : asset('assets/img/room-default.jpg')) ?>"
                                 alt="Podgląd zdjęcia sali">
                        </div>
                        <input class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" type="file"
                               id="image" name="image" accept="image/jpeg,image/png,image/webp"
                               data-image-input="#image-preview">
                        <div class="form-text">JPG, PNG lub WEBP, maks. 2 MB.</div>
                        <div class="invalid-feedback"><?= e($errors['image'] ?? 'Nieprawidłowy plik.') ?></div>

                        <?php if (!empty($imageValue)): ?>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                <label class="form-check-label small" for="remove_image">Usuń obecne zdjęcie</label>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="detail-panel mb-4">
                        <h2 class="h6 fw-bold mb-3">Widoczność</h2>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active"
                                   value="1" <?= (int) $form['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Sala aktywna w katalogu</label>
                        </div>
                        <p class="small text-muted-soft mb-0 mt-2">Nieaktywne sale nie pojawiają się w wyszukiwarce i nie można ich rezerwować.</p>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="bi bi-check2 me-1"></i> <?= $room !== null ? 'Zapisz zmiany' : 'Dodaj salę' ?>
                        </button>
                        <a class="btn btn-outline-secondary" href="<?= e(url('admin/rooms.php')) ?>">Anuluj</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
