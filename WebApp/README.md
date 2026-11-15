# BookIt — aplikacja PHP / MySQL / Bootstrap

Nowoczesny, responsywny system rezerwacji sal konferencyjnych. Projekt zaliczeniowy
zrealizowany w czystym PHP 8 (bez frameworka), z bazą MySQL/MariaDB i interfejsem
opartym na Bootstrapie 5.3 oraz własnym JavaScript (bez jQuery).

## Funkcje

- **Konta użytkowników** — rejestracja, logowanie, wylogowanie, profil, zmiana hasła.
- **Bezpieczeństwo** — hasła hashowane `password_hash()` (bcrypt), regeneracja ID sesji,
  ciasteczka `HttpOnly` + `SameSite=Lax`, tokeny CSRF dla każdego formularza POST,
  przygotowane zapytania PDO, walidacja po stronie serwera i klienta, limit prób logowania.
- **Katalog sal** — wyszukiwanie, filtry (liczba osób, budżet), sortowanie, paginacja.
- **Rezerwacje (CRUD)** — tworzenie z kontrolą kolizji terminów (transakcja + `FOR UPDATE`),
  edycja, anulowanie, usuwanie, filtrowanie po statusie i historii.
- **Panel administratora** — pulpit ze statystykami, CRUD sal (w tym upload zdjęć),
  zarządzanie rezerwacjami (potwierdzanie/anulowanie/usuwanie), zarządzanie użytkownikami i rolami.
- **Interaktywny front-end** — walidacja formularzy Bootstrapa, podgląd zajętości dnia
  przez Fetch API, wyliczanie ceny na żywo, modal potwierdzenia operacji, przełącznik
  jasny/ciemny motyw, wskaźnik siły hasła, filtry tabel.
- **Responsywność** — układ od telefonu do dużego monitora, menu mobilne, tabele przewijane.

## Stos technologiczny

| Warstwa | Technologia |
| --- | --- |
| Backend | PHP 8.1+ (PDO, sesje) |
| Baza danych | MySQL 8 / MariaDB 10.6+ (InnoDB, utf8mb4) |
| Frontend | Bootstrap 5.3, Bootstrap Icons, własny CSS, JavaScript (ES2020) |
| Serwer | Apache (XAMPP) lub wbudowany serwer PHP |

## Wymagania

- PHP **8.1** lub nowszy z rozszerzeniami `pdo_mysql`, `mbstring`, `fileinfo`,
- MySQL 8 / MariaDB 10.6+ (np. z XAMPP, Laragon lub Docker),
- przeglądarka z obsługą JavaScript.

## Uruchomienie (XAMPP)

1. Skopiuj katalog projektu do `C:\xampp\htdocs\bookit`.
2. Uruchom **Apache** i **MySQL** w panelu XAMPP.
3. Zaimportuj bazę:
   - phpMyAdmin → **Import** → wybierz `database/bookit.sql`, lub
   - terminal: `mysql -u root -p < database/bookit.sql`.
4. Sprawdź dane połączenia w `config/config.php` (domyślnie `127.0.0.1`, użytkownik `root`, puste hasło, baza `bookit`).
5. Otwórz `http://localhost/bookit/public/`.

> Adres bazowy jest wykrywany automatycznie na podstawie `DOCUMENT_ROOT`,
> więc aplikacja działa zarówno w podkatalogu, jak i z wbudowanym serwerem.
> W razie potrzeby można go wymusić ręcznie w `config/config.php` lub zmienną `BASE_URL`.

## Uruchomienie (wbudowany serwer PHP)

```bash
cd bookit
php -S localhost:8000 -t public
```

Aplikacja będzie dostępna pod `http://localhost:8000`. Baza musi być wcześniej zaimportowana.

## Uruchomienie (Docker, opcjonalnie)

```bash
docker run -d --name bookit-db -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=bookit -p 3306:3306 mariadb:11
docker exec -i bookit-db mariadb -uroot -psecret bookit < database/bookit.sql
DB_PASS=secret php -S localhost:8000 -t public
```

## Konta demonstracyjne

| Rola | E-mail | Hasło |
| --- | --- | --- |
| Administrator | `admin@bookit.pl` | `Admin123!` |
| Użytkownik | `jan@example.com` | `User123!` |
| Użytkownik | `marta@example.com` | `User123!` |

## Struktura projektu

```
bookit/
├── app/                     # logika aplikacji
│   ├── bootstrap.php        # start: sesja, baza, repozytoria
│   ├── Database.php         # połączenie PDO
│   ├── UserRepository.php   # operacje na użytkownikach
│   ├── RoomRepository.php   # operacje na salach + zajętość
│   ├── ReservationRepository.php # rezerwacje + kontrola kolizji
│   ├── Validator.php        # walidacja danych
│   ├── auth.php             # logowanie, role, limity prób
│   ├── csrf.php             # tokeny CSRF i strona błędu
│   └── helpers.php          # funkcje pomocnicze (escaping, formatowanie)
├── config/config.php        # konfiguracja bazy i aplikacji
├── database/bookit.sql      # eksport bazy: struktura + dane startowe
├── public/                  # katalog publiczny (document root)
│   ├── index.php            # strona główna
│   ├── rooms.php            # katalog sal z filtrami
│   ├── room.php             # szczegóły sali + formularz rezerwacji
│   ├── login.php / register.php / logout.php
│   ├── reservations.php     # moje rezerwacje (CRUD)
│   ├── reservation-edit.php
│   ├── profile.php
│   ├── api/availability.php # JSON z zajętością dnia
│   ├── admin/               # panel administratora
│   ├── includes/            # nagłówek, stopka, partiale
│   ├── assets/              # CSS, JS, grafiki
│   └── uploads/rooms/       # zdjęcia wgrywane przez administratora
└── README.md
```

## Baza danych

Trzy tabele połączone kluczami obcymi:

- `users` — konta i role (`user` / `admin`), unikalny e-mail,
- `rooms` — sale (pojemność, cena za godzinę, lokalizacja, zdjęcie, aktywność),
- `reservations` — rezerwacje (użytkownik, sala, data, godziny, status, kwota),
  z indeksami `(room_id, reservation_date, start_time)` oraz `ON DELETE CASCADE`
  dla użytkownika i `ON DELETE RESTRICT` dla sali.

Dodatkowo widok `v_reservations` łączy rezerwacje z użytkownikiem i salą.

## Bezpieczeństwo — szczegóły

- `password_hash()` / `password_verify()` z automatycznym `password_needs_rehash()`,
- `session_regenerate_id(true)` po logowaniu (ochrona przed session fixation),
- każdy formularz POST zawiera token CSRF weryfikowany przez `hash_equals()`,
- wszystkie zapytania korzystają z prepared statements PDO (bez konkatenacji danych),
- cały output jest escapowany funkcją `e()` (`htmlspecialchars`),
- upload zdjęć: weryfikacja MIME (`finfo`), rozmiaru i poprawności obrazu (`getimagesize`),
- limit 5 nieudanych logowań → 5 minut blokady,
- role sprawdzane po stronie serwera (`require_admin()`), nie tylko w menu.

## Obsługa typowych problemów

| Problem | Rozwiązanie |
| --- | --- |
| „Brak połączenia z bazą danych” | Sprawdź `config/config.php` i czy baza `bookit` została zaimportowana. |
| Puste style / brak ikon | Sprawdź dostęp do CDN (Bootstrap, Google Fonts) lub podmień na pliki lokalne. |
| Po imporcie brak danych | Upewnij się, że import wykonano na bazie `bookit` (plik zawiera `CREATE DATABASE`). |
| Zdjęcia nie zapisują się | Nadaj katalogowi `public/uploads/rooms` prawa zapisu. |
| Adresy bez stylów po zmianie katalogu | Ustaw `base_url` zgodnie z rzeczywistą ścieżką aplikacji. |

## Autor

Projekt zaliczeniowy — Andrii Kazakov. Kod źródłowy dostępny na GitHubie
w gałęzi **WebApp**: <https://github.com/Baaambyk/Andrii_Kazakov_5Tp/tree/WebApp>.
