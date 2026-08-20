-- ================================================================
-- Happy Pratheep Recharge — MySQL schema
-- Run this in your local MySQL to create the database + tables
-- before running `php artisan migrate` (or import in phpMyAdmin).
-- ================================================================

-- 1) Create the database (if it doesn't already exist)
CREATE DATABASE IF NOT EXISTS `pratheep_recharge`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `pratheep_recharge`;

-- 2) Users table — phone is REQUIRED and unique
CREATE TABLE IF NOT EXISTS `users` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(255)    NOT NULL,
  `email`             VARCHAR(255)    NOT NULL,
  `phone`             VARCHAR(15)     NOT NULL COMMENT 'LK phone, stored as +94771234567',
  `avatar`            VARCHAR(255)    NULL,
  `email_verified_at` TIMESTAMP       NULL DEFAULT NULL,
  `password`          VARCHAR(255)    NOT NULL,
  `remember_token`    VARCHAR(100)    NULL,
  `created_at`        TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Password reset tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email`      VARCHAR(255) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Sessions table (for file/database session drivers)
CREATE TABLE IF NOT EXISTS `sessions` (
  `id`            VARCHAR(255) NOT NULL,
  `user_id`       BIGINT UNSIGNED NULL,
  `ip_address`    VARCHAR(45)  NULL,
  `user_agent`    TEXT         NULL,
  `payload`       LONGTEXT     NOT NULL,
  `last_activity` INT          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Optional: create a dedicated MySQL user for the app
-- (change the password to something strong!)
-- ================================================================
-- CREATE USER 'pratheep'@'localhost' IDENTIFIED BY 'your_strong_password';
-- GRANT ALL PRIVILEGES ON `pratheep_recharge`.* TO 'pratheep'@'localhost';
-- FLUSH PRIVILEGES;
