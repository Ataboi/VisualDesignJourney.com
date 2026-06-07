-- Visual Design Journey — Database Schema
-- Engine: MySQL 8.x | Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)     NOT NULL,
  `email`         VARCHAR(255)    NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `avatar`        VARCHAR(255)    DEFAULT NULL,
  `bio`           TEXT            DEFAULT NULL,
  `website`       VARCHAR(255)    DEFAULT NULL,
  `location`      VARCHAR(100)    DEFAULT NULL,
  `is_onboarded`  TINYINT(1)      NOT NULL DEFAULT 0,
  `is_admin`      TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- BOARDS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `boards` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED    NOT NULL,
  `title`        VARCHAR(150)    NOT NULL,
  `description`  TEXT            DEFAULT NULL,
  `cover_image`  VARCHAR(255)    DEFAULT NULL,
  `style_tags`   VARCHAR(500)    DEFAULT NULL,  -- comma-separated
  `is_draft`     TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_boards_user`    (`user_id`),
  KEY `idx_boards_draft`   (`is_draft`),
  KEY `idx_boards_created` (`created_at`),
  CONSTRAINT `fk_boards_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- BOARD IMAGES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `board_images` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `board_id`   INT UNSIGNED  NOT NULL,
  `image_url`  VARCHAR(255)  NOT NULL,
  `sort_order` SMALLINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_boardimages_board` (`board_id`),
  CONSTRAINT `fk_boardimages_board` FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- BOARD LIKES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `board_likes` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `board_id`   INT UNSIGNED  NOT NULL,
  `user_id`    INT UNSIGNED  NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_boardlikes` (`board_id`, `user_id`),
  KEY `idx_boardlikes_user` (`user_id`),
  CONSTRAINT `fk_boardlikes_board` FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_boardlikes_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- FOLLOWS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `follows` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `follower_id`  INT UNSIGNED  NOT NULL,
  `following_id` INT UNSIGNED  NOT NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_follow` (`follower_id`, `following_id`),
  KEY `idx_follows_following` (`following_id`),
  CONSTRAINT `fk_follows_follower`  FOREIGN KEY (`follower_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_follows_following` FOREIGN KEY (`following_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED  NOT NULL,  -- recipient
  `from_user_id` INT UNSIGNED  DEFAULT NULL,
  `type`         ENUM('like','follow','comment','mention') NOT NULL,
  `board_id`     INT UNSIGNED  DEFAULT NULL,
  `is_read`      TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`    (`user_id`, `is_read`),
  KEY `idx_notif_created` (`created_at`),
  CONSTRAINT `fk_notif_user`      FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_from_user` FOREIGN KEY (`from_user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notif_board`     FOREIGN KEY (`board_id`)
    REFERENCES `boards` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- VIBE STYLES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vibe_styles` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`           VARCHAR(80)   NOT NULL,
  `label`          VARCHAR(100)  NOT NULL,
  `palette_colors` VARCHAR(500)  DEFAULT NULL,  -- JSON array of hex codes
  `font_primary`   VARCHAR(100)  DEFAULT NULL,
  `font_secondary` VARCHAR(100)  DEFAULT NULL,
  `thumbnail`      VARCHAR(255)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vibe_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- SEED: VIBE STYLES
-- ─────────────────────────────────────────
INSERT IGNORE INTO `vibe_styles` (`slug`, `label`, `palette_colors`, `font_primary`, `font_secondary`) VALUES
('brutalist',     'Brutalist',     '["#000000","#FFFFFF","#FF0000"]',        'Space Grotesk', 'Inter'),
('soft-minimal',  'Soft Minimal',  '["#F5F0EB","#D4C4B0","#8B7355"]',        'Playfair Display', 'Inter'),
('neon-cyber',    'Neon Cyber',    '["#0D0D0D","#FF00FF","#00FFFF"]',        'Space Mono', 'Inter'),
('cottagecore',   'Cottagecore',   '["#E8D5C4","#A8C5A0","#C4956A"]',        'Lora', 'Inter'),
('high-fashion',  'High Fashion',  '["#1A1A1A","#C9B99A","#E8E4DC"]',        'Cormorant Garamond', 'Inter'),
('y2k-revival',   'Y2K Revival',   '["#FF69B4","#00CED1","#FFD700"]',        'VT323', 'Inter'),
('japandi',       'Japandi',       '["#F4F1EC","#8B8680","#3D3530"]',        'Noto Serif JP', 'Inter'),
('maximalist',    'Maximalist',    '["#2D1B69","#FF6B35","#F7C59F"]',        'Abril Fatface', 'Inter');

SET FOREIGN_KEY_CHECKS = 1;
