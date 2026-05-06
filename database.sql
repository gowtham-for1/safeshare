-- =============================================
-- database.sql — SafeShare Pro Database Setup
-- Run this in phpMyAdmin or MySQL CLI
-- =============================================

-- 1. Create database
CREATE DATABASE IF NOT EXISTS safeshare
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE safeshare;

-- 2. Create files table
CREATE TABLE IF NOT EXISTS files (
    id             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    original_name  VARCHAR(255)    NOT NULL,
    stored_name    VARCHAR(255)    NOT NULL,
    token          VARCHAR(128)    NOT NULL,
    file_path      VARCHAR(512)    NOT NULL,
    file_size      INT UNSIGNED    NOT NULL DEFAULT 0,
    expiry_time    DATETIME        NOT NULL,
    max_downloads  INT UNSIGNED    NULL DEFAULT NULL,
    download_count INT UNSIGNED    NOT NULL DEFAULT 0,
    password       VARCHAR(255)    NULL DEFAULT NULL,
    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY  uq_token (token),
    INDEX       idx_expiry (expiry_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Done! Your table is ready.