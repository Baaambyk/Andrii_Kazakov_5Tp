<?php
require __DIR__ . '/../app/bootstrap.php';

$filters = [
    'q'         => trim((string) ($_GET['q'] ?? '')),
    'capacity'  => (int) ($_GET['capacity'] ?? 0),
    'max_price' => (float) ($_GET['max_price'] ?? 0),
    'sort'      => (string) ($_GET['sort'] ?? 'newest'),
];

$pagination = $rooms->paginate($filters, max(1, (int) ($_GET['page'] ?? 1)), 9, true);
$pageTitle = 'Sale konferencyjne';

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Start</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sale</li>
            </ol>
        </nav>
        <div class="row align-items-end g-3">
            <div class="col-lg-8">
                <h1 class="h2 mb-2">Sale konferencyjne i szkoleniowe</h1>
                <p class="text-muted-soft mb-0">
                    Wybierz przestrzeń dopasowaną do liczby uczestników, lokalizacji i budżetu.
                    Każda sala ma aktualny kalendarz dostępności.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="badge badge-soft-primary fs-6 px-3 py-2">
                    <i class="bi bi-collection me-1"></i>
                    <?= (int) $pagination['total'] ?> <?= $pagination['total'] === 1 ? 'sala' : 'sal' ?>
                </span>
            </div>
        </div>
    </div>
</div>

<section class="section pt-4">
    <div class="container">
        <form class="filter-bar mb-4" method="get" action="<?= e(url('rooms.php')) ?>" role="search">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label" for="q">Szukaj</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input class="form-control" id="q" type="search" name="q"
                               value="<?= e($filters['q']) ?>" placeholder="Nazwa, miasto, wyposażenie…">
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="capacity">Liczba osób</label>
                    <select class="form-select" id="capacity" name="capacity" data-auto-submit>
                        <option value="">Dowolna</option>
                        <?php foreach ([4, 8, 12, 20, 30, 50] as $cap): ?>
                            <option value="<?= $cap ?>" <?= selected($filters['capacity'], $cap) ?>>od <?= $cap ?> osób</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="max_price">Budżet / godz.</label>
                    <select class="form-select" id="max_price" name="max_price" data-auto-submit>
                        <option value="">Dowolny</option>
                        <?php foreach ([80, 120, 180, 250, 400] as $price): ?>
                            <option value="<?= $price ?>" <?= selected($filters['max_price'], $price) ?>>do <?= $price ?> zł</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label" for="sort">Sortowanie</label>
                    <select class="form-select" id="sort" name="sort" data-auto-submit>
                        <option value="newest" <?= selected($filters['sort'], 'newest') ?>>Najnowsze</option>
                        <option value="price_asc" <?= selected($filters['sort'], 'price_asc') ?>>Cena rosnąco</option>
                        <option value="price_desc" <?= selected($filters['sort'], 'price_desc') ?>>Cena malejąco</option>
                        <option value="capacity_desc" <?= selected($filters['sort'], 'capacity_desc') ?>>Największe</option>
                        <option value="name" <?= selected($filters['sort'], 'name') ?>>Nazwa A–Z</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">
                        <i class="bi bi-funnel me-1"></i> Filtruj
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('rooms.php')) ?>" title="Wyczyść filtry" aria-label="Wyczyść filtry">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>

        <?php if ($pagination['items'] === []): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h2 class="h5 fw-bold mt-3">Brak sal spełniających kryteria</h2>
                <p class="text-muted-soft mb-4">Spróbuj zwiększyć budżet, zmniejszyć liczbę osób lub wyczyść filtry.</p>
                <a class="btn btn-primary" href="<?= e(url('rooms.php')) ?>">Wyczyść filtry</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($pagination['items'] as $room): ?>
                    <div class="col-md-6 col-xl-4">
                        <?php require __DIR__ . '/includes/room-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <span class="result-count">
                    Strona <?= (int) $pagination['page'] ?> z <?= (int) $pagination['pages'] ?>
                    · <?= (int) $pagination['total'] ?> wyników
                </span>
                <span class="result-count">
                    <i class="bi bi-clock me-1"></i>Godziny otwarcia: 8:00–22:00
                </span>
            </div>

            <?php require __DIR__ . '/includes/pagination.php'; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
