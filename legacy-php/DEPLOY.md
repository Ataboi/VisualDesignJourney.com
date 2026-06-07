# Visual Design Journey v2 — Safe cPanel/FTP Deploy

This release is a **hybrid v2 deploy**:

- Public UI and tools are updated with the VDJ v2 / VibeKit visual system.
- Existing PHP admin remains at `/admin`.
- Existing APIs remain at `/api/*`.
- Blog slugs, sitemap, feeds, uploads, AdSense, and Search Console-sensitive SEO paths are preserved.
- No Laravel/Filament admin cutover is performed in this release.

## 1. Mandatory Backup

Do not upload this release before all three backups exist.

1. cPanel File Manager or FTP:
   - Download the full current `public_html` folder.
   - Confirm these paths are included:
     - `config/db.local.php`
     - `config/adsense.local.php`
     - `uploads/`
     - `admin/`
     - `.htaccess`
2. phpMyAdmin:
   - Export the production database as SQL.
   - Use "Quick" export only if the SQL file downloads successfully.
3. Local safe copy:
   - Store both backups outside the deploy folder.
   - Name them with date/time, for example `vdj-before-v2-20260607`.

## 2. Files That Must Not Be Overwritten

When uploading via FTP/cPanel, preserve server-only files:

- `config/db.local.php`
- `config/adsense.local.php`
- `config/.installed`
- `uploads/`
- `cache/`
- Any host-generated backup archives

The deploy ZIP may contain `config/db.php`; that is safe. It must not contain production credentials.

## 3. Upload Order

Upload in this order:

1. New/changed includes and public pages:
   - `includes/v2_catalog.php`
   - `v2-library.php`
   - `tools.php`
   - `includes/header.php`
   - `includes/functions.php`
2. Styles:
   - `assets/css/vdj-v2.css`
   - `admin/admin.css`
3. Routing:
   - `.htaccess`
4. Admin label only:
   - `admin/_sidebar.php`
5. Docs:
   - `DEPLOY.md`
   - `ROLLBACK-V2.md`

## 4. Smoke Test Checklist

After upload, test these URLs before doing anything else:

| URL | Expected |
| --- | --- |
| `/` | Existing VDJ home/explore loads |
| `/tools` | New VDJ v2 tools hub loads |
| `/font-pairings` | V2 collection page loads |
| `/gradients` | V2 CSS resource page loads |
| `/blog` | Existing blog index loads |
| `/blog/{known-slug}/` | Existing blog post loads, no slug loss |
| `/sitemap.xml` | XML response |
| `/feed.xml` | XML response |
| `/admin/` | Existing PHP admin login/dashboard loads |
| `/api/newsletter.php` | Endpoint still responds to valid POST only |
| `/uploads/...` | Existing media file loads |

## 5. SEO Checks

Use page source on `/blog/{known-slug}/`:

- Canonical URL is unchanged.
- `hreflang` links are present for translated posts.
- JSON-LD scripts are valid JSON.
- No V2 tool URL is accidentally rendering as a blog post.

Use `/admin/seo-audit.php`:

- "Raw JSON-LD script tags" should be `0` for protected files.
- Existing media and slug checks should remain readable.

## 6. What This Release Does Not Do

- It does not replace `/admin` with Laravel or Filament.
- It does not rename existing database tables.
- It does not delete legacy PHP files.
- It does not move uploads.
- It does not require Composer or Node on the hosting server.

## 7. Future Laravel Cutover

Laravel/Filament should only be introduced after:

- A verified full backup process exists.
- A staging copy or temporary protected path is available.
- Existing auth, blog, board, API, and admin parity is tested.
- `/admin-v2` has feature parity with the PHP admin.
