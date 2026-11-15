<?php
/**
 * Wspólna stopka i dołączane skrypty.
 */
?>
</main>

<footer class="app-footer mt-auto">
    <div class="container">
        <div class="row gy-4 py-5">
            <div class="col-lg-4">
                <a class="navbar-brand d-flex align-items-center gap-2 mb-3" href="<?= e(url('index.php')) ?>">
                    <span class="brand-mark"><i class="bi bi-calendar2-check"></i></span>
                    <span class="brand-text text-white">Book<span>It</span></span>
                </a>
                <p class="text-secondary mb-4" style="max-width: 320px">
                    Nowoczesna rezerwacja sal konferencyjnych i przestrzeni spotkań.
                    Sprawdź dostępność i zarezerwuj termin w kilka kliknięć.
                </p>
                <div class="d-flex gap-2">
                    <a class="footer-social" href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a class="footer-social" href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a class="footer-social" href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2 offset-lg-1">
                <h6 class="footer-heading">Aplikacja</h6>
                <ul class="footer-links">
                    <li><a href="<?= e(url('index.php')) ?>">Strona główna</a></li>
                    <li><a href="<?= e(url('rooms.php')) ?>">Lista sal</a></li>
                    <li><a href="<?= e(url('reservations.php')) ?>">Moje rezerwacje</a></li>
                    <li><a href="<?= e(url('register.php')) ?>">Rejestracja</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="footer-heading">Kontakt</h6>
                <ul class="footer-links">
                    <li><a href="mailto:kontakt@bookit.pl">kontakt@bookit.pl</a></li>
                    <li><a href="tel:+48221234567">+48 22 123 45 67</a></li>
                    <li><span>pon.–pt. 8:00–18:00</span></li>
                    <li><span>Warszawa, ul. Grzybowska 12</span></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="footer-heading">Dostępność sal</h6>
                <p class="text-secondary small mb-3">Sale są dostępne od poniedziałku do niedzieli w godzinach 8:00–22:00.</p>
                <a class="btn btn-outline-light btn-sm" href="<?= e(url('rooms.php')) ?>">
                    <i class="bi bi-calendar2-plus me-1"></i> Zarezerwuj salę
                </a>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 py-4">
            <span class="small">© <?= date('Y') ?> <?= e(APP_NAME) ?>. Projekt zaliczeniowy PHP / MySQL / Bootstrap.</span>
            <span class="small">Zbudowane z <i class="bi bi-heart-fill text-danger"></i> w Polsce</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
