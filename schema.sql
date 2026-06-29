-- ================================================================
-- SimbIoT — Database Schema v1.0
-- Dibuat: 29 Juni 2026
--
-- Opsi A: Import file ini via phpMyAdmin
-- Opsi B: Biarkan setup.php yang membuat tabel otomatis
--         (kedua opsi bisa dipakai, tidak akan konflik)
-- ================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- ----------------------------------------------------------------
-- Tabel: users (akun admin platform)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`    DATETIME     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Tabel: feedback (masukan dari pengunjung)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedback` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100) NOT NULL,
  `role`         ENUM('siswa','guru','mahasiswa_dosen','pengembang') NOT NULL,
  `message`      TEXT         NOT NULL,
  `response`     TEXT         NULL,
  `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Tabel: setup_lock (cegah setup.php dijalankan ulang)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `setup_lock` (
  `id`        INT      NOT NULL DEFAULT 1,
  `locked_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
