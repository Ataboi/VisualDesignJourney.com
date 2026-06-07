-- ── Blog i18n Migration ─────────────────────────────────────────────────────
-- Adds Turkish (tr) and German (de) translation columns to public content tables.
-- Falls back to English originals when translations are empty.
-- Safe to run multiple times (uses IF NOT EXISTS / column check approach).

ALTER TABLE `blog_posts`
    ADD COLUMN IF NOT EXISTS `title_tr`           VARCHAR(500)  NOT NULL DEFAULT '' AFTER `title`,
    ADD COLUMN IF NOT EXISTS `title_de`           VARCHAR(500)  NOT NULL DEFAULT '' AFTER `title_tr`,
    ADD COLUMN IF NOT EXISTS `slug_tr`            VARCHAR(500)  NOT NULL DEFAULT '' AFTER `slug`,
    ADD COLUMN IF NOT EXISTS `slug_de`            VARCHAR(500)  NOT NULL DEFAULT '' AFTER `slug_tr`,
    ADD COLUMN IF NOT EXISTS `excerpt_tr`         TEXT          NULL AFTER `excerpt`,
    ADD COLUMN IF NOT EXISTS `excerpt_de`         TEXT          NULL AFTER `excerpt_tr`,
    ADD COLUMN IF NOT EXISTS `content_tr`         LONGTEXT      NULL AFTER `content`,
    ADD COLUMN IF NOT EXISTS `content_de`         LONGTEXT      NULL AFTER `content_tr`,
    ADD COLUMN IF NOT EXISTS `seo_title_tr`       VARCHAR(200)  NOT NULL DEFAULT '' AFTER `seo_title`,
    ADD COLUMN IF NOT EXISTS `seo_title_de`       VARCHAR(200)  NOT NULL DEFAULT '' AFTER `seo_title_tr`,
    ADD COLUMN IF NOT EXISTS `seo_description_tr` VARCHAR(300)  NOT NULL DEFAULT '' AFTER `seo_description`,
    ADD COLUMN IF NOT EXISTS `seo_description_de` VARCHAR(300)  NOT NULL DEFAULT '' AFTER `seo_description_tr`;

-- Indexes for fast slug lookup by language
ALTER TABLE `blog_posts`
    ADD INDEX IF NOT EXISTS `idx_slug_tr` (`slug_tr`(191)),
    ADD INDEX IF NOT EXISTS `idx_slug_de` (`slug_de`(191));

ALTER TABLE `blog_categories`
    ADD COLUMN IF NOT EXISTS `name_tr`        VARCHAR(200) NOT NULL DEFAULT '' AFTER `name`,
    ADD COLUMN IF NOT EXISTS `name_de`        VARCHAR(200) NOT NULL DEFAULT '' AFTER `name_tr`,
    ADD COLUMN IF NOT EXISTS `slug_tr`        VARCHAR(200) NOT NULL DEFAULT '' AFTER `slug`,
    ADD COLUMN IF NOT EXISTS `slug_de`        VARCHAR(200) NOT NULL DEFAULT '' AFTER `slug_tr`,
    ADD COLUMN IF NOT EXISTS `description_tr` TEXT         NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `description_de` TEXT         NULL AFTER `description_tr`;

ALTER TABLE `blog_categories`
    ADD INDEX IF NOT EXISTS `idx_blog_categories_slug_tr` (`slug_tr`(191)),
    ADD INDEX IF NOT EXISTS `idx_blog_categories_slug_de` (`slug_de`(191));

ALTER TABLE `blog_tags`
    ADD COLUMN IF NOT EXISTS `name_tr` VARCHAR(200) NOT NULL DEFAULT '' AFTER `name`,
    ADD COLUMN IF NOT EXISTS `name_de` VARCHAR(200) NOT NULL DEFAULT '' AFTER `name_tr`,
    ADD COLUMN IF NOT EXISTS `slug_tr` VARCHAR(200) NOT NULL DEFAULT '' AFTER `slug`,
    ADD COLUMN IF NOT EXISTS `slug_de` VARCHAR(200) NOT NULL DEFAULT '' AFTER `slug_tr`;

ALTER TABLE `blog_tags`
    ADD INDEX IF NOT EXISTS `idx_blog_tags_slug_tr` (`slug_tr`(191)),
    ADD INDEX IF NOT EXISTS `idx_blog_tags_slug_de` (`slug_de`(191));

ALTER TABLE `boards`
    ADD COLUMN IF NOT EXISTS `title_tr`       VARCHAR(150) NOT NULL DEFAULT '' AFTER `title`,
    ADD COLUMN IF NOT EXISTS `title_de`       VARCHAR(150) NOT NULL DEFAULT '' AFTER `title_tr`,
    ADD COLUMN IF NOT EXISTS `description_tr` TEXT         NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `description_de` TEXT         NULL AFTER `description_tr`,
    ADD COLUMN IF NOT EXISTS `slug`           VARCHAR(180) NOT NULL DEFAULT '' AFTER `id`,
    ADD COLUMN IF NOT EXISTS `slug_tr`        VARCHAR(180) NOT NULL DEFAULT '' AFTER `slug`,
    ADD COLUMN IF NOT EXISTS `slug_de`        VARCHAR(180) NOT NULL DEFAULT '' AFTER `slug_tr`;

ALTER TABLE `boards`
    ADD INDEX IF NOT EXISTS `idx_boards_slug` (`slug`),
    ADD INDEX IF NOT EXISTS `idx_boards_slug_tr` (`slug_tr`),
    ADD INDEX IF NOT EXISTS `idx_boards_slug_de` (`slug_de`);

ALTER TABLE `vibe_styles`
    ADD COLUMN IF NOT EXISTS `label_tr`          VARCHAR(100) NOT NULL DEFAULT '' AFTER `label`,
    ADD COLUMN IF NOT EXISTS `label_de`          VARCHAR(100) NOT NULL DEFAULT '' AFTER `label_tr`,
    ADD COLUMN IF NOT EXISTS `description`       TEXT         NULL AFTER `thumbnail`,
    ADD COLUMN IF NOT EXISTS `description_tr`    TEXT         NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `description_de`    TEXT         NULL AFTER `description_tr`,
    ADD COLUMN IF NOT EXISTS `font_pairing_tr`   VARCHAR(220) NOT NULL DEFAULT '' AFTER `font_secondary`,
    ADD COLUMN IF NOT EXISTS `font_pairing_de`   VARCHAR(220) NOT NULL DEFAULT '' AFTER `font_pairing_tr`,
    ADD COLUMN IF NOT EXISTS `related_tags`      VARCHAR(500) NOT NULL DEFAULT '' AFTER `description_de`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `bio_tr` TEXT NULL AFTER `bio`,
    ADD COLUMN IF NOT EXISTS `bio_de` TEXT NULL AFTER `bio_tr`;

-- ── Post-import content fixes for visualde_vibe dump ────────────────────────
-- The supplied dump already includes translated blog_posts content/slug fields.
-- These updates complete translated taxonomy/vibe labels and board SEO slugs.

UPDATE `blog_categories` SET
  `name_tr` = CASE `slug`
    WHEN 'creative' THEN 'Yaratıcı'
    WHEN 'design' THEN 'Tasarım'
    WHEN 'typography' THEN 'Tipografi'
    WHEN 'graphic-design' THEN 'Grafik Tasarım'
    WHEN 'interface-design' THEN 'Arayüz Tasarımı'
    WHEN 'illustration' THEN 'İllüstrasyon'
    WHEN 'innovation' THEN 'İnovasyon'
    WHEN 'artificial-intelligence' THEN 'Yapay Zeka'
    WHEN 'package-design' THEN 'Ambalaj Tasarımı'
    WHEN 'top-100-graphic-designers' THEN 'En İyi 100 Grafik Tasarımcı'
    WHEN 'fine-arts' THEN 'Güzel Sanatlar'
    WHEN 'gallery' THEN 'Galeri'
    WHEN 'art-deco-design' THEN 'Art Deco Tasarımı'
    WHEN 'art-nouveau-design' THEN 'Art Nouveau Tasarımı'
    WHEN 'contemporary-design' THEN 'Çağdaş Tasarım'
    WHEN 'flat-design' THEN 'Flat Tasarım'
    WHEN 'grunge-design' THEN 'Grunge Tasarım'
    WHEN 'minimalist-design' THEN 'Minimalist Tasarım'
    WHEN 'new-york-design' THEN 'New York Tasarımı'
    WHEN 'psychedelic-design' THEN 'Psikedelik Tasarım'
    WHEN 'retro-design' THEN 'Retro Tasarım'
    WHEN 'news' THEN 'Haberler'
    WHEN 'freelance-graphic-design' THEN 'Freelance Grafik Tasarım'
    WHEN 'online-marketing' THEN 'Online Pazarlama'
    WHEN 'tutorials' THEN 'Eğitimler'
    WHEN 'web-development' THEN 'Web Geliştirme'
    WHEN 'design-and-branding' THEN 'Tasarım ve Markalaşma'
    WHEN 'general' THEN 'Genel'
    WHEN 'career-advice' THEN 'Kariyer Tavsiyeleri'
    WHEN 'marketing' THEN 'Pazarlama'
    WHEN 'digital-marketing' THEN 'Dijital Pazarlama'
    WHEN 'design-and-creativity' THEN 'Tasarım ve Yaratıcılık'
    WHEN 'design-and-architecture' THEN 'Tasarım ve Mimarlık'
    WHEN 'gaming' THEN 'Oyun'
    ELSE `name_tr`
  END,
  `name_de` = CASE `slug`
    WHEN 'creative' THEN 'Kreativ'
    WHEN 'design' THEN 'Design'
    WHEN 'typography' THEN 'Typografie'
    WHEN 'graphic-design' THEN 'Grafikdesign'
    WHEN 'interface-design' THEN 'Interface Design'
    WHEN 'illustration' THEN 'Illustration'
    WHEN 'innovation' THEN 'Innovation'
    WHEN 'artificial-intelligence' THEN 'Künstliche Intelligenz'
    WHEN 'package-design' THEN 'Verpackungsdesign'
    WHEN 'top-100-graphic-designers' THEN 'Top 100 Grafikdesigner'
    WHEN 'fine-arts' THEN 'Bildende Kunst'
    WHEN 'gallery' THEN 'Galerie'
    WHEN 'art-deco-design' THEN 'Art-Deco-Design'
    WHEN 'art-nouveau-design' THEN 'Art-Nouveau-Design'
    WHEN 'contemporary-design' THEN 'Zeitgenössisches Design'
    WHEN 'flat-design' THEN 'Flat Design'
    WHEN 'grunge-design' THEN 'Grunge Design'
    WHEN 'minimalist-design' THEN 'Minimalistisches Design'
    WHEN 'new-york-design' THEN 'New-York-Design'
    WHEN 'psychedelic-design' THEN 'Psychedelisches Design'
    WHEN 'retro-design' THEN 'Retro Design'
    WHEN 'news' THEN 'News'
    WHEN 'freelance-graphic-design' THEN 'Freelance-Grafikdesign'
    WHEN 'online-marketing' THEN 'Online-Marketing'
    WHEN 'tutorials' THEN 'Tutorials'
    WHEN 'web-development' THEN 'Webentwicklung'
    WHEN 'design-and-branding' THEN 'Design und Branding'
    WHEN 'general' THEN 'Allgemein'
    WHEN 'career-advice' THEN 'Karrieretipps'
    WHEN 'marketing' THEN 'Marketing'
    WHEN 'digital-marketing' THEN 'Digitales Marketing'
    WHEN 'design-and-creativity' THEN 'Design und Kreativität'
    WHEN 'design-and-architecture' THEN 'Design und Architektur'
    WHEN 'gaming' THEN 'Gaming'
    ELSE `name_de`
  END,
  `slug_tr` = CASE `slug`
    WHEN 'creative' THEN 'yaratici'
    WHEN 'design' THEN 'tasarim'
    WHEN 'typography' THEN 'tipografi'
    WHEN 'graphic-design' THEN 'grafik-tasarim'
    WHEN 'interface-design' THEN 'arayuz-tasarimi'
    WHEN 'illustration' THEN 'illustrasyon'
    WHEN 'innovation' THEN 'inovasyon'
    WHEN 'artificial-intelligence' THEN 'yapay-zeka'
    WHEN 'package-design' THEN 'ambalaj-tasarimi'
    WHEN 'top-100-graphic-designers' THEN 'en-iyi-100-grafik-tasarimci'
    WHEN 'fine-arts' THEN 'guzel-sanatlar'
    WHEN 'gallery' THEN 'galeri'
    WHEN 'art-deco-design' THEN 'art-deco-tasarimi'
    WHEN 'art-nouveau-design' THEN 'art-nouveau-tasarimi'
    WHEN 'contemporary-design' THEN 'cagdas-tasarim'
    WHEN 'flat-design' THEN 'flat-tasarim'
    WHEN 'grunge-design' THEN 'grunge-tasarim'
    WHEN 'minimalist-design' THEN 'minimalist-tasarim'
    WHEN 'new-york-design' THEN 'new-york-tasarimi'
    WHEN 'psychedelic-design' THEN 'psikedelik-tasarim'
    WHEN 'retro-design' THEN 'retro-tasarim'
    WHEN 'news' THEN 'haberler'
    WHEN 'freelance-graphic-design' THEN 'freelance-grafik-tasarim'
    WHEN 'online-marketing' THEN 'online-pazarlama'
    WHEN 'tutorials' THEN 'egitimler'
    WHEN 'web-development' THEN 'web-gelistirme'
    WHEN 'design-and-branding' THEN 'tasarim-ve-markalasma'
    WHEN 'general' THEN 'genel'
    WHEN 'career-advice' THEN 'kariyer-tavsiyeleri'
    WHEN 'marketing' THEN 'pazarlama'
    WHEN 'digital-marketing' THEN 'dijital-pazarlama'
    WHEN 'design-and-creativity' THEN 'tasarim-ve-yaraticilik'
    WHEN 'design-and-architecture' THEN 'tasarim-ve-mimarlik'
    WHEN 'gaming' THEN 'oyun'
    ELSE `slug_tr`
  END,
  `slug_de` = CASE `slug`
    WHEN 'creative' THEN 'kreativ'
    WHEN 'design' THEN 'design'
    WHEN 'typography' THEN 'typografie'
    WHEN 'graphic-design' THEN 'grafikdesign'
    WHEN 'interface-design' THEN 'interface-design'
    WHEN 'illustration' THEN 'illustration'
    WHEN 'innovation' THEN 'innovation'
    WHEN 'artificial-intelligence' THEN 'kuenstliche-intelligenz'
    WHEN 'package-design' THEN 'verpackungsdesign'
    WHEN 'top-100-graphic-designers' THEN 'top-100-grafikdesigner'
    WHEN 'fine-arts' THEN 'bildende-kunst'
    WHEN 'gallery' THEN 'galerie'
    WHEN 'art-deco-design' THEN 'art-deco-design'
    WHEN 'art-nouveau-design' THEN 'art-nouveau-design'
    WHEN 'contemporary-design' THEN 'zeitgenoessisches-design'
    WHEN 'flat-design' THEN 'flat-design'
    WHEN 'grunge-design' THEN 'grunge-design'
    WHEN 'minimalist-design' THEN 'minimalistisches-design'
    WHEN 'new-york-design' THEN 'new-york-design'
    WHEN 'psychedelic-design' THEN 'psychedelisches-design'
    WHEN 'retro-design' THEN 'retro-design'
    WHEN 'news' THEN 'news'
    WHEN 'freelance-graphic-design' THEN 'freelance-grafikdesign'
    WHEN 'online-marketing' THEN 'online-marketing'
    WHEN 'tutorials' THEN 'tutorials'
    WHEN 'web-development' THEN 'webentwicklung'
    WHEN 'design-and-branding' THEN 'design-und-branding'
    WHEN 'general' THEN 'allgemein'
    WHEN 'career-advice' THEN 'karrieretipps'
    WHEN 'marketing' THEN 'marketing'
    WHEN 'digital-marketing' THEN 'digitales-marketing'
    WHEN 'design-and-creativity' THEN 'design-und-kreativitaet'
    WHEN 'design-and-architecture' THEN 'design-und-architektur'
    WHEN 'gaming' THEN 'gaming'
    ELSE `slug_de`
  END;

UPDATE `blog_tags` SET
  `name_tr` = CASE `slug`
    WHEN 'creative-process' THEN 'Yaratıcı süreç'
    WHEN 'design-blog' THEN 'Tasarım blogu'
    WHEN 'design-trends' THEN 'Tasarım trendleri'
    WHEN 'graphic-design' THEN 'Grafik tasarım'
    WHEN 'motion-graphics' THEN 'Hareketli grafikler'
    WHEN 'ux-design' THEN 'UX tasarımı'
    WHEN 'visual-design' THEN 'Görsel tasarım'
    WHEN 'web-design-trends' THEN 'Web tasarım trendleri'
    WHEN 'logo-design' THEN 'Logo tasarımı'
    WHEN 'adobe-photoshop' THEN 'Adobe Photoshop'
    WHEN 'website-design' THEN 'Web sitesi tasarımı'
    WHEN 'business-logos' THEN 'İşletme logoları'
    WHEN 'stock-photos' THEN 'Stok fotoğraflar'
    WHEN 'custom-icons' THEN 'Özel ikonlar'
    WHEN 'print-designs' THEN 'Baskı tasarımları'
    ELSE `name_tr`
  END,
  `name_de` = CASE `slug`
    WHEN 'creative-process' THEN 'Kreativer Prozess'
    WHEN 'design-blog' THEN 'Designblog'
    WHEN 'design-trends' THEN 'Designtrends'
    WHEN 'graphic-design' THEN 'Grafikdesign'
    WHEN 'motion-graphics' THEN 'Motion Graphics'
    WHEN 'ux-design' THEN 'UX-Design'
    WHEN 'visual-design' THEN 'Visuelles Design'
    WHEN 'web-design-trends' THEN 'Webdesign-Trends'
    WHEN 'logo-design' THEN 'Logo-Design'
    WHEN 'adobe-photoshop' THEN 'Adobe Photoshop'
    WHEN 'website-design' THEN 'Webseitendesign'
    WHEN 'business-logos' THEN 'Unternehmenslogos'
    WHEN 'stock-photos' THEN 'Stockfotos'
    WHEN 'custom-icons' THEN 'Individuelle Icons'
    WHEN 'print-designs' THEN 'Printdesigns'
    ELSE `name_de`
  END,
  `slug_tr` = CASE `slug`
    WHEN 'creative-process' THEN 'yaratici-surec'
    WHEN 'design-blog' THEN 'tasarim-blogu'
    WHEN 'design-trends' THEN 'tasarim-trendleri'
    WHEN 'graphic-design' THEN 'grafik-tasarim'
    WHEN 'motion-graphics' THEN 'hareketli-grafikler'
    WHEN 'ux-design' THEN 'ux-tasarimi'
    WHEN 'visual-design' THEN 'gorsel-tasarim'
    WHEN 'web-design-trends' THEN 'web-tasarim-trendleri'
    WHEN 'logo-design' THEN 'logo-tasarimi'
    WHEN 'adobe-photoshop' THEN 'adobe-photoshop'
    WHEN 'website-design' THEN 'web-sitesi-tasarimi'
    WHEN 'business-logos' THEN 'isletme-logolari'
    WHEN 'stock-photos' THEN 'stok-fotograflar'
    WHEN 'custom-icons' THEN 'ozel-ikonlar'
    WHEN 'print-designs' THEN 'baski-tasarimlari'
    ELSE `slug_tr`
  END,
  `slug_de` = CASE `slug`
    WHEN 'creative-process' THEN 'kreativer-prozess'
    WHEN 'design-blog' THEN 'designblog'
    WHEN 'design-trends' THEN 'designtrends'
    WHEN 'graphic-design' THEN 'grafikdesign'
    WHEN 'motion-graphics' THEN 'motion-graphics'
    WHEN 'ux-design' THEN 'ux-design'
    WHEN 'visual-design' THEN 'visuelles-design'
    WHEN 'web-design-trends' THEN 'webdesign-trends'
    WHEN 'logo-design' THEN 'logo-design'
    WHEN 'adobe-photoshop' THEN 'adobe-photoshop'
    WHEN 'website-design' THEN 'webseitendesign'
    WHEN 'business-logos' THEN 'unternehmenslogos'
    WHEN 'stock-photos' THEN 'stockfotos'
    WHEN 'custom-icons' THEN 'individuelle-icons'
    WHEN 'print-designs' THEN 'printdesigns'
    ELSE `slug_de`
  END;

UPDATE `vibe_styles` SET
  `label_tr` = CASE `slug`
    WHEN 'brutalist' THEN 'Brütalist'
    WHEN 'soft-minimal' THEN 'Soft Minimal'
    WHEN 'neon-cyber' THEN 'Neon Cyber'
    WHEN 'cottagecore' THEN 'Cottagecore'
    WHEN 'high-fashion' THEN 'High Fashion'
    WHEN 'y2k-revival' THEN 'Y2K Revival'
    WHEN 'japandi' THEN 'Japandi'
    WHEN 'maximalist' THEN 'Maksimalist'
    ELSE `label_tr`
  END,
  `label_de` = CASE `slug`
    WHEN 'brutalist' THEN 'Brutalistisch'
    WHEN 'soft-minimal' THEN 'Soft Minimal'
    WHEN 'neon-cyber' THEN 'Neon Cyber'
    WHEN 'cottagecore' THEN 'Cottagecore'
    WHEN 'high-fashion' THEN 'High Fashion'
    WHEN 'y2k-revival' THEN 'Y2K Revival'
    WHEN 'japandi' THEN 'Japandi'
    WHEN 'maximalist' THEN 'Maximalistisch'
    ELSE `label_de`
  END;

UPDATE `boards`
SET `slug` = LOWER(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`title`, '&', 'and'), '/', '-'), '.', ''), ',', ''), ':', ''), ';', ''), '  ', ' '), ' ', '-'), '--', '-')
)
WHERE COALESCE(`slug`, '') = '';
