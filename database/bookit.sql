-- =====================================================================
--  BookIt — system rezerwacji sal
--  Plik eksportu bazy danych (MySQL 8 / MariaDB 10.6+)
--  Import: mysql -u root -p < database/bookit.sql
--  lub w phpMyAdmin: Import -> wybierz ten plik
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `bookit`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `bookit`;

DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------
--  Użytkownicy
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(80)  NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(30)  DEFAULT NULL,
  `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Sale konferencyjne
-- ---------------------------------------------------------------------
CREATE TABLE `rooms` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(120) NOT NULL,
  `description`    TEXT NOT NULL,
  `capacity`       SMALLINT UNSIGNED NOT NULL,
  `price_per_hour` DECIMAL(10,2) NOT NULL,
  `location`       VARCHAR(120) NOT NULL,
  `equipment`      VARCHAR(255) DEFAULT NULL,
  `image`          VARCHAR(255) DEFAULT NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rooms_active` (`is_active`),
  KEY `idx_rooms_capacity` (`capacity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Rezerwacje
-- ---------------------------------------------------------------------
CREATE TABLE `reservations` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `room_id`          INT UNSIGNED NOT NULL,
  `title`            VARCHAR(150) NOT NULL,
  `reservation_date` DATE NOT NULL,
  `start_time`       TIME NOT NULL,
  `end_time`         TIME NOT NULL,
  `guests`           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `status`           ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `notes`            VARCHAR(500) DEFAULT NULL,
  `total_price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_res_room_date` (`room_id`, `reservation_date`, `start_time`),
  KEY `idx_res_user` (`user_id`),
  KEY `idx_res_status` (`status`),
  CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Dane startowe
--  Hasła:  admin@bookit.pl / Admin123!   |   jan@example.com / User123!
-- ---------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `role`, `created_at`) VALUES
(1, 'Anna Kowalska', 'admin@bookit.pl', '$2y$10$OCjGgP29rgSIGOxIPOcmveHSWu.IxXqyY1V/Gzc/cjZ5MtJjuUoTO', '+48 601 200 300', 'admin', NOW() - INTERVAL 120 DAY),
(2, 'Jan Nowak', 'jan@example.com', '$2y$10$amsXtOS1JSl2Vf8.d3CzB.tQdYtZ3QzvpmkERa52RSrP7oVWm7Aqq', '+48 602 300 400', 'user', NOW() - INTERVAL 60 DAY),
(3, 'Marta Wiśniewska', 'marta@example.com', '$2y$10$amsXtOS1JSl2Vf8.d3CzB.tQdYtZ3QzvpmkERa52RSrP7oVWm7Aqq', '+48 603 400 500', 'user', NOW() - INTERVAL 35 DAY),
(4, 'Piotr Zając', 'piotr@example.com', '$2y$10$amsXtOS1JSl2Vf8.d3CzB.tQdYtZ3QzvpmkERa52RSrP7oVWm7Aqq', NULL, 'user', NOW() - INTERVAL 12 DAY);

INSERT INTO `rooms` (`id`, `name`, `description`, `capacity`, `price_per_hour`, `location`, `equipment`, `image`, `is_active`) VALUES
(1, 'Sala Horizon', 'Przestronna sala konferencyjna z panoramicznym widokiem na miasto. Idealna na prezentacje produktowe, szkolenia i spotkania zarządu. Duże okna zapewniają naturalne światło przez cały dzień.', 18, 180.00, 'Warszawa, ul. Grzybowska 12',
 'Rzutnik 4K, ekran 120", tablica suchościeralna, Wi-Fi 1 Gb/s, system wideokonferencyjny, klimatyzacja', 'assets/img/room-1.jpg', 1),
(2, 'Sala Aurora', 'Kameralna sala spotkań zaprojektowana w spokojnej, jasnej stylistyce. Doskonała na rozmowy rekrutacyjne, konsultacje i warsztaty w małym gronie.', 8, 95.00, 'Warszawa, ul. Puławska 45',
 'Telewizor 65", tablica flipchart, Wi-Fi, ekspres do kawy, klimatyzacja', 'assets/img/room-2.jpg', 1),
(3, 'Studio Nova', 'Kreatywna przestrzeń z elastycznym układem mebli, przeznaczona do burzy mózgów, design sprintów i sesji zdjęciowych. Ściany w pełni mobilne, dużo przestrzeni na pracę w grupach.', 14, 140.00, 'Kraków, ul. Zabłocie 8',
 'Ściany mobilne, rzutnik Full HD, głośniki, Wi-Fi, pufy i strefa lounge', 'assets/img/room-3.jpg', 1),
(4, 'Sala Delta', 'Sala szkoleniowa w układzie teatralnym lub warsztatowym. Wyposażona w sprzęt do prowadzenia szkoleń i nagrywania prezentacji.', 24, 220.00, 'Wrocław, ul. Legnicka 51',
 'Rzutnik laserowy, mikrofon bezprzewodowy, kamera do transmisji, tablica, Wi-Fi', 'assets/img/room-4.jpg', 1),
(5, 'Salka Lumen', 'Ciche i przytulne miejsce do pracy grupowej oraz spotkań jeden na jeden. Naturalne drewno i miękkie światło sprzyjają skupieniu.', 6, 60.00, 'Poznań, ul. Półwiejska 20',
 'Monitor 43", tablica korkowa, Wi-Fi, lampka do czytania, woda i owoce', 'assets/img/room-5.jpg', 1),
(6, 'Przestrzeń Vertex', 'Nowoczesna sala eventowa z zapleczem cateringowym i sceną. Sprawdzi się podczas konferencji, premier i spotkań networkingowych.', 60, 320.00, 'Gdańsk, ul. Olivia Star 3',
 'Scena 6 m, nagłośnienie, 4 mikrofony, oświetlenie sceniczne, Wi-Fi, zaplecze cateringowe', 'assets/img/room-6.jpg', 1);

INSERT INTO `reservations`
(`user_id`, `room_id`, `title`, `reservation_date`, `start_time`, `end_time`, `guests`, `status`, `notes`, `total_price`, `created_at`) VALUES
(2, 1, 'Prezentacja wyników kwartalnych', CURDATE() + INTERVAL 3 DAY, '10:00:00', '12:00:00', 15, 'confirmed', 'Potrzebny dodatkowy mikrofon krawatowy.', 360.00, NOW() - INTERVAL 4 DAY),
(2, 3, 'Warsztat design thinking', CURDATE() + INTERVAL 7 DAY, '09:00:00', '13:00:00', 12, 'pending', NULL, 560.00, NOW() - INTERVAL 2 DAY),
(3, 2, 'Rozmowy rekrutacyjne — backend', CURDATE() + INTERVAL 1 DAY, '12:00:00', '15:00:00', 4, 'confirmed', 'Trzy osoby, potrzebna woda niegazowana.', 285.00, NOW() - INTERVAL 1 DAY),
(3, 5, 'Retrospektywa zespołu', CURDATE() + INTERVAL 5 DAY, '16:00:00', '18:00:00', 6, 'pending', NULL, 120.00, NOW() - INTERVAL 3 HOUR),
(4, 4, 'Szkolenie BHP', CURDATE() + INTERVAL 12 DAY, '08:00:00', '14:00:00', 24, 'pending', 'Prosimy o ustawienie krzeseł w rzędach.', 1320.00, NOW() - INTERVAL 1 DAY),
(2, 6, 'Spotkanie networkingowe', CURDATE() + INTERVAL 20 DAY, '17:00:00', '21:00:00', 50, 'confirmed', 'Catering dla 50 osób, wegetariańskie opcje.', 1280.00, NOW() - INTERVAL 6 DAY),
(2, 1, 'Spotkanie zespołu produktu', CURDATE() - INTERVAL 6 DAY, '11:00:00', '12:30:00', 10, 'confirmed', NULL, 270.00, NOW() - INTERVAL 10 DAY),
(3, 2, 'Konsultacja z klientem', CURDATE() - INTERVAL 2 DAY, '14:00:00', '15:00:00', 3, 'cancelled', 'Klient przełożył spotkanie.', 95.00, NOW() - INTERVAL 5 DAY);

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--  Widok pomocniczy: rezerwacje z danymi użytkownika i sali
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_reservations` AS
SELECT
  r.`id`, r.`title`, r.`reservation_date`, r.`start_time`, r.`end_time`,
  r.`guests`, r.`status`, r.`total_price`,
  u.`id` AS user_id, u.`name` AS user_name, u.`email` AS user_email,
  s.`id` AS room_id, s.`name` AS room_name, s.`location` AS room_location
FROM `reservations` r
JOIN `users` u ON u.`id` = r.`user_id`
JOIN `rooms` s ON s.`id` = r.`room_id`;
