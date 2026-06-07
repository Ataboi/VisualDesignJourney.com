# Visual Design Journey v2 — Laravel cPanel/FTP Deploy

This deploy converts the current VDJ root into one Laravel application using the VibeKit UI/tool base. The existing VDJ database remains the source of truth. Legacy PHP is kept in `legacy-php/` for reference and rollback, but public traffic is handled by Laravel.

## Mandatory Backups

Do not upload before these exist:

1. Full cPanel/FTP file backup of the current site.
2. phpMyAdmin SQL export of the production database.
3. Separate copy of `uploads/`, `.env` or old config files, and any host-only credentials.

## Local Build

Run locally before zipping:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan vdj:validate-json-ld
php artisan route:list
php artisan migrate --pretend
```

The hosting server does not need Composer or Node if `vendor/` and `public/build/` are uploaded.
If your local database is migrated, also run `php artisan db:seed --class=VdjToolSeeder --force` to preview populated tool pages.
Run the same seeder after migrations on production; it only upserts VDJ v2 tool starter content and does not touch existing VDJ users, boards, or blog posts.

## Production `.env`

Create `.env` from `.env.example` and set:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://visualdesignjourney.com`
- production MySQL credentials
- `SESSION_DRIVER=database` after migrations create `sessions`

Run `php artisan key:generate` locally if no production key exists, then keep that key stable.

## Upload Shape

Upload these Laravel directories/files together:

- `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `vendor/`
- `artisan`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`
- `.htaccess`, `.env.example`, `DEPLOY.md`, `ROLLBACK-V2.md`
- existing `uploads/`, `assets/`, `ads.txt`, `robots.txt`

Do not upload production credentials into Git or public archives.

## Production Run Order

1. Put the site in a short maintenance window.
2. Upload the package without deleting `uploads/`, `ads.txt`, host-only config backups, or the SQL dump.
3. Copy production values into `.env`.
4. Run `php artisan migrate --force`.
5. Run `php artisan db:seed --class=VdjToolSeeder --force`.
6. Run `php artisan optimize:clear && php artisan optimize`.
7. Test the must-pass URLs before announcing the update.

## Must-Pass URLs

- `/`
- `/blog`
- `/blog/{known-slug}/`
- `/tr/blog/{known-tr-slug}/`
- `/de/blog/{known-de-slug}/`
- `/tools`
- `/font-pairings`
- `/color-palettes`
- `/buttons`
- `/tr/tools` should 301 to `/tools`
- `/gradient-builder`
- `/contrast-checker`
- `/font-pairing-preview` should 301 to `/font-preview`
- `/board/{id}-{slug}`
- `/profile/{username}`
- `/create`
- `/saved`
- `/settings`
- `/notifications`
- `/followers/{username}`
- `/newsletter-prefs?token={known-token}`
- `/admin`
- `/api/newsletter.php`
- `/api/sponsor-lead.php`
- `/api/submit-source.php`
- `/api/bookmark-source.php`
- `/api/discover-for-board.php`
- `/api/like.php`
- `/api/save.php`
- `/api/comment.php`
- `/api/follow.php`
- `/api/search-suggest.php?q=design`
- `/api/random.php`
- `/api/palette.php?board_id={id}`
- `/api/media.php?path={known-upload-path}`
- `/sitemap.xml`
- `/feed.xml`

## SEO Checks

- Blog canonical remains the same clean URL.
- `hreflang` appears on translated posts.
- JSON-LD decodes; run `php artisan vdj:validate-json-ld`.
- `/admin` → VDJ Health → Structured Data shows no failures.
- `ads.txt`, uploads, sitemap, and RSS remain accessible.
