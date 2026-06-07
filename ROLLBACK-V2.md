# Visual Design Journey v2 — Laravel Rollback

Use rollback only after confirming the file backup and SQL dump exist.

## Fast File Rollback

1. Put the site into maintenance mode if available in cPanel.
2. Restore the pre-Laravel file backup over the current root.
3. Restore the old `.htaccess`.
4. Restore old `config/` credential files if they were changed.
5. Keep `uploads/` intact unless the backup proves it is newer.

The local `legacy-php/` and `backup-before-laravel-v2/` folders are reference copies only; the safest rollback source is the independent cPanel/FTP backup taken before deploy.

## Database Rollback

This Laravel cutover should not drop or rename existing VDJ tables. If a migration or manual change damages production data:

1. Confirm the SQL dump opens and contains `users`, `boards`, `board_images`, `blog_posts`, `blog_categories`, `blog_tags`, `newsletter_subscribers`, and `sponsor_leads`.
2. In phpMyAdmin, restore the SQL dump.
3. Recheck admin login and public blog slugs.

VDJ v2 tool seed data lives in new Laravel tool tables such as `categories`, `font_pairings`, `color_palettes`, `css_snippets`, `ui_patterns`, `type_tools`, and `color_references`. Those tables can be ignored during an old-PHP rollback unless the SQL dump shows they overwrote existing production data.

## Must-Pass After Rollback

- `/admin/` opens the old PHP admin.
- `/blog` opens.
- At least five known blog slugs return 200.
- `/sitemap.xml` returns XML.
- Existing upload URLs render.
- Admin login works.
