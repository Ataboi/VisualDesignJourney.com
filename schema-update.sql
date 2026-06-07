-- ============================================================
-- schema-update.sql
-- Visual Design Journey — feature additions
-- Run in phpMyAdmin or via: mysql -u user -p dbname < schema-update.sql
-- ============================================================

-- Board saves (bookmarks)
CREATE TABLE IF NOT EXISTS board_saves (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  board_id   INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_save  (user_id, board_id),
  KEY        idx_user (user_id),
  KEY        idx_board (board_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Board comments
CREATE TABLE IF NOT EXISTS board_comments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  board_id   INT NOT NULL,
  user_id    INT NOT NULL,
  content    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_board (board_id),
  KEY idx_user  (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verified curator badge on users
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NOT NULL DEFAULT 0;

-- Board save count cache (optional denormalisation)
ALTER TABLE boards ADD COLUMN IF NOT EXISTS save_count INT NOT NULL DEFAULT 0;

-- Board view counter
ALTER TABLE boards ADD COLUMN IF NOT EXISTS view_count INT NOT NULL DEFAULT 0;

-- Newsletter subscribers
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(255) NOT NULL,
  name       VARCHAR(100),
  status     ENUM('active','unsubscribed') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sponsor / partnership leads
CREATE TABLE IF NOT EXISTS sponsor_leads (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  company    VARCHAR(160),
  budget     VARCHAR(40),
  message    TEXT NOT NULL,
  status     ENUM('new','contacted','won','lost') DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- External source metadata for curated images.
ALTER TABLE boards MODIFY COLUMN cover_image VARCHAR(1000) DEFAULT NULL;
ALTER TABLE board_images MODIFY COLUMN image_url VARCHAR(1000) NOT NULL;
ALTER TABLE board_images ADD COLUMN IF NOT EXISTS source_url VARCHAR(500) DEFAULT NULL;
ALTER TABLE board_images ADD COLUMN IF NOT EXISTS source_platform VARCHAR(80) DEFAULT NULL;
ALTER TABLE board_images ADD COLUMN IF NOT EXISTS credit VARCHAR(160) DEFAULT NULL;
ALTER TABLE board_images ADD COLUMN IF NOT EXISTS caption VARCHAR(255) DEFAULT NULL;

-- Social bookmarking / discovery moderation queue.
CREATE TABLE IF NOT EXISTS source_bookmarks (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT DEFAULT NULL,
  source_url      VARCHAR(1000) NOT NULL,
  source_platform VARCHAR(120),
  title           VARCHAR(255),
  description     VARCHAR(500),
  image_url       VARCHAR(1000),
  suggested_tags  VARCHAR(255),
  status          ENUM('pending','approved','rejected') DEFAULT 'pending',
  board_id        INT DEFAULT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at     DATETIME DEFAULT NULL,
  UNIQUE KEY uq_source_url (source_url(191)),
  KEY idx_status (status),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Newsletter unsubscribe token
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS unsubscribe_token VARCHAR(64) DEFAULT NULL;
ALTER TABLE newsletter_subscribers ADD UNIQUE KEY IF NOT EXISTS uq_token (unsubscribe_token);

-- Invoice notes for sponsor leads
ALTER TABLE sponsor_leads ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL;

-- ── Traffic & Click Tracking ──────────────────────────────────────────────────
-- Guest-aware, privacy-safe (visitor_hash = sha256(daily-salt+IP+UA), no raw IP stored)
CREATE TABLE IF NOT EXISTS page_views (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  page_path    VARCHAR(500) NOT NULL,
  page_title   VARCHAR(255),
  user_id      INT NULL,
  is_guest     TINYINT(1) NOT NULL DEFAULT 1,
  referrer     VARCHAR(500),
  device_type  ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
  visitor_hash VARCHAR(64),
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at),
  KEY idx_path    (page_path(191)),
  KEY idx_guest   (is_guest),
  KEY idx_visitor (visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS board_clicks (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  board_id     INT NOT NULL,
  click_source VARCHAR(80) DEFAULT 'unknown',
  user_id      INT NULL,
  is_guest     TINYINT(1) NOT NULL DEFAULT 1,
  visitor_hash VARCHAR(64),
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_board   (board_id),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS source_feeds (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(160) NOT NULL,
  feed_url    VARCHAR(1000) NOT NULL,
  tags        VARCHAR(255),
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  last_run_at DATETIME DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_feed_url (feed_url(191)),
  KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deploy webhook settings and structured history.
-- The admin page also creates these tables automatically if they are missing.
CREATE TABLE IF NOT EXISTS deploy_settings (
  id                  TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  webhook_url         TEXT NULL,
  status_url          TEXT NULL,
  webhook_secret      VARCHAR(255) NOT NULL DEFAULT '',
  environment         VARCHAR(32) NOT NULL DEFAULT 'production',
  sync_state          VARCHAR(32) NOT NULL DEFAULT 'idle',
  last_status         VARCHAR(120) DEFAULT NULL,
  last_http_code      INT DEFAULT NULL,
  last_response       MEDIUMTEXT NULL,
  last_deploy_at      DATETIME DEFAULT NULL,
  last_checked_at     DATETIME DEFAULT NULL,
  in_progress_until   DATETIME DEFAULT NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sync_state (sync_state),
  KEY idx_last_deploy (last_deploy_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO deploy_settings (id, environment, sync_state)
VALUES (1, 'production', 'idle');

CREATE TABLE IF NOT EXISTS deploy_history (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  action            VARCHAR(40) NOT NULL,
  environment       VARCHAR(32) NOT NULL DEFAULT 'production',
  status            VARCHAR(32) NOT NULL DEFAULT 'pending',
  http_code         INT DEFAULT NULL,
  payload_json      MEDIUMTEXT NULL,
  response_body     MEDIUMTEXT NULL,
  error_message     TEXT NULL,
  triggered_by      VARCHAR(120) DEFAULT NULL,
  idempotency_key   VARCHAR(80) DEFAULT NULL,
  started_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at       DATETIME DEFAULT NULL,
  duration_ms       INT DEFAULT NULL,
  KEY idx_action (action),
  KEY idx_status (status),
  KEY idx_started (started_at),
  KEY idx_idempotency (idempotency_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
