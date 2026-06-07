-- ── Blog Import Schema ────────────────────────────────────────────────────
-- Visual Design Journey — WordPress import tables
-- Run after schema.sql (uses same DB)

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `wp_id`           INT UNSIGNED    NOT NULL DEFAULT 0,
  `title`           VARCHAR(500)    NOT NULL DEFAULT '',
  `slug`            VARCHAR(500)    NOT NULL DEFAULT '',
  `content`         LONGTEXT        NOT NULL,
  `excerpt`         TEXT            NOT NULL DEFAULT '',
  `featured_image`  VARCHAR(500)    NOT NULL DEFAULT '',
  `author`          VARCHAR(100)    NOT NULL DEFAULT '',
  `seo_title`       VARCHAR(200)    NOT NULL DEFAULT '',
  `seo_description` VARCHAR(300)    NOT NULL DEFAULT '',
  `published_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_posts_slug` (`slug`(191)),
  KEY `idx_blog_posts_published_at` (`published_at`),
  KEY `idx_blog_posts_wp_id` (`wp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200)  NOT NULL DEFAULT '',
  `slug`        VARCHAR(200)  NOT NULL DEFAULT '',
  `description` TEXT          NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_categories_slug` (`slug`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id`   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200)  NOT NULL DEFAULT '',
  `slug` VARCHAR(200)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_tags_slug` (`slug`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_post_categories` (
  `post_id`     INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`, `category_id`),
  KEY `idx_bpc_category_id` (`category_id`),
  CONSTRAINT `fk_bpc_post`     FOREIGN KEY (`post_id`)     REFERENCES `blog_posts`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpc_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_post_tags` (
  `post_id` INT UNSIGNED NOT NULL,
  `tag_id`  INT UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  KEY `idx_bpt_tag_id` (`tag_id`),
  CONSTRAINT `fk_bpt_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpt_tag`  FOREIGN KEY (`tag_id`)  REFERENCES `blog_tags`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
