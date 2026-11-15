<?php
require __DIR__ . '/../app/bootstrap.php';

$featured    = $rooms->featured(3);
$stats       = $reservations->stats();
$roomsCount  = $rooms->countActive();
$usersCount  = $users->count();
$capacitySum = $rooms->totalCapacity();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="hero-badge">
                    <i class="bi bi-stars"></i> Rezerwacja online w 60 sekund
                </span>
                <h1>Znajdź salę, która <span class="text-gradient">pasuje</span> do Twojego spotkania</h1>
                <p class="hero-lead">
                    Sale konferencyjne, szkoleniowe i eventowe w całej Polsce. Sprawdź wolne terminy
                    w czasie rzeczywistym, zarezerwuj online i zarządzaj spotkaniami z jednego miejsca.
                </p>
                <div class="hero-points">
                    <span><i class="bi bi-check-circle-fill"></i>Natychmiastowa dostępność</span>
                    <span><i class="bi bi-check-circle-fill"></i>Bez ukrytych opłat</span>
                    <span><i class="bi bi-check-circle-fill"></i>Potwierdzenie online</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light btn-lg fw-bold" href="<?= e(url('rooms.php')) ?>">
                        Przeglądaj sale <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a class="btn btn-outline-light btn-lg" href="#jak-to-dziala">Jak to działa?</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-card">
                    <h2 class="mb-1">Sprawdź dostępność</h2>
                    <p class="text-secondary small mb-3">Wybierz kryteria, a pokażemy sale gotowe na Twoje spotkanie.</p>
                    <form action="<?= e(url('rooms.php')) ?>" method="get" role="search">
                        <div class="mb-3">
                            <label class="form-label" for="hero-q">Miasto, nazwa lub wyposażenie</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input class="form-control" id="hero-q" type="search" name="q"
                                       placeholder="np. Warszawa, rzutnik, taras">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" for="hero-capacity">Liczba osób</label>
                                <select class="form-select" id="hero-capacity" name="capacity">
                                    <option value="">Dowolna</option>
                                    <option value="4">od 4 osób</option>
                                    <option value="8">od 8 osób</option>
                                    <option value="12">od 12 osób</option>
                                    <option value="20">od 20 osób</option>
                                    <option value="30">od 30 osób</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="hero-budget">Budżet / godz.</label>
                                <select class="form-select" id="hero-budget" name="max_price">
                                    <option value="">Dowolny</option>
                                    <option value="80">do 80 zł</option>
                                    <option value="120">do 120 zł</option>
                                    <option value="180">do 180 zł</option>
                                    <option value="250">do 250 zł</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100 mt-3" type="submit">
                            <i class="bi bi-calendar2-search me-1"></i> Pokaż dostępne sale
                        </button>
                        <div class="hero-visual-note">
                            <span class="dot"></span>
                            <span>Potwierdzenie rezerwacji zwykle w ciągu 15 minut</span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container stats-strip">
    <div class="card">
        <div class="row g-0">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value"><?= $roomsCount ?></div>
                    <div class="stat-label">dostępnych sal</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value"><?= (int) $stats['confirmed'] ?></div>
                    <div class="stat-label">potwierdzonych rezerwacji</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value"><?= $usersCount ?></div>
                    <div class="stat-label">zarejestrowanych osób</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-value"><?= $capacitySum ?></div>
                    <div class="stat-label">miejsc łącznie</div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <span class="eyebrow"><i class="bi bi-door-open"></i> Nasze sale</span>
                <h2 class="section-title h2 mt-2 mb-0">Polecane przestrzenie</h2>
                <p class="text-muted-soft mb-0 mt-2">Najczęściej wybierane sale — sprawdź, czy pasują do Twojego planu.</p>
            </div>
            <a class="btn btn-outline-primary" href="<?= e(url('rooms.php')) ?>">
                Zobacz wszystkie <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if ($featured === []): ?>
            <div class="empty-state">
                <i class="bi bi-door-closed"></i>
                <p class="mb-0 mt-3">Brak aktywnych sal. Dodaj je w panelu administratora.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($featured as $room): ?>
                    <div class="col-md-6 col-lg-4">
                        <?php require __DIR__ . '/includes/room-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-soft" id="jak-to-dziala">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 560px">
            <span class="eyebrow"><i class="bi bi-signpost-split"></i> Prosty proces</span>
            <h2 class="section-title h2 mt-2">Rezerwacja w trzech krokach</h2>
            <p class="text-muted-soft mt-2 mb-0">Bez telefonów, maili i czekania na odpowiedź. Wszystko załatwiasz online.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <h3 class="h5 fw-bold">Wybierz salę</h3>
                    <p class="text-muted-soft mb-0">
                        Filtruj sale po mieście, liczbie osób i budżecie. Zobacz zdjęcia,
                        wyposażenie oraz aktualne terminy.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">02</div>
                    <h3 class="h5 fw-bold">Sprawdź termin</h3>
                    <p class="text-muted-soft mb-0">
                        Wybierz datę i godziny — system od razu pokaże zajęte przedziały
                        i wyceni rezerwację.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">03</div>
                    <h3 class="h5 fw-bold">Potwierdź online</h3>
                    <p class="text-muted-soft mb-0">
                        Zarezerwuj termin jednym kliknięciem. Status rezerwacji
                        i wszystkie szczegóły znajdziesz w swoim koncie.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6">
                <div class="row g-4 h-100">
                    <div class="col-sm-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-lightning-charge"></i></div>
                            <h3 class="h6 fw-bold">Dostępność na żywo</h3>
                            <p class="text-muted-soft small mb-0">Zajętość sal sprawdzana jest w momencie wyboru terminu.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                            <h3 class="h6 fw-bold">Bezpieczne konta</h3>
                            <p class="text-muted-soft small mb-0">Hasła szyfrowane algorytmem bcrypt, sesje chronione tokenami CSRF.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-clock-history"></i></div>
                            <h3 class="h6 fw-bold">Elastyczne godziny</h3>
                            <p class="text-muted-soft small mb-0">Sale dostępne codziennie w godzinach 8:00–22:00, także w weekendy.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-sliders"></i></div>
                            <h3 class="h6 fw-bold">Pełna kontrola</h3>
                            <p class="text-muted-soft small mb-0">Edytuj i anuluj rezerwacje, a administrator zarządza całą bazą sal.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="cta-banner h-100 d-flex flex-column justify-content-center">
                    <span class="eyebrow text-white-50 mb-2" style="color: rgba(255,255,255,.7) !important">Gotowy na spotkanie?</span>
                    <h2 class="section-title h2 mb-3">Zarezerwuj salę jeszcze dziś</h2>
                    <p class="mb-4" style="color: rgba(255,255,255,.8)">
                        Dołącz do zespołów, które planują spotkania bez zbędnych formalności.
                        Załóż konto w minutę i zarezerwuj pierwszy termin.
                    </p>
                    <div class="d-flex flex-wrap gap-2 position-relative" style="z-index: 1">
                        <a class="btn btn-light fw-bold" href="<?= e(url('register.php')) ?>">Załóż darmowe konto</a>
                        <a class="btn btn-outline-light" href="<?= e(url('rooms.php')) ?>">Zobacz sale</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
