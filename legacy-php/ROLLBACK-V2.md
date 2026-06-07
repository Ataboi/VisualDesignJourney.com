# Visual Design Journey v2 — Rollback Notes

Use this only if the v2 deploy breaks public routing, admin access, or blog slugs.

## Fast File Rollback

Restore these files from the pre-v2 backup:

- `.htaccess`
- `tools.php`
- `includes/header.php`
- `includes/functions.php`
- `admin/_sidebar.php`
- `admin/admin.css`

Then remove these v2-only files if needed:

- `includes/v2_catalog.php`
- `v2-library.php`
- `assets/css/vdj-v2.css`

## Database Rollback

This release does not require destructive database migrations.

If a later deploy added migrations and you need to restore:

1. Open phpMyAdmin.
2. Drop the damaged database only if you have confirmed the SQL backup exists.
3. Import the pre-v2 SQL export.
4. Verify `users`, `boards`, `blog_posts`, `blog_categories`, `blog_tags`, `newsletter_subscribers`, and `sponsor_leads`.

## Must-Pass Checks After Rollback

- `/admin/` opens.
- `/blog` opens.
- A known blog slug opens.
- `/sitemap.xml` returns XML.
- Existing uploads render.
- Login still works for an admin user.
