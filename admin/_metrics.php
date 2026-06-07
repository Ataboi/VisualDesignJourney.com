<?php
// Shared admin health/readiness helpers.

function adminBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return ($i === 0 ? (string)$bytes : number_format($bytes, 1)) . ' ' . $units[$i];
}

function adminScalar(PDO $db, string $sql, int|string $fallback = 0): int|string {
    try {
        $value = $db->query($sql)->fetchColumn();
        return $value === false ? $fallback : $value;
    } catch (Throwable $e) {
        error_log('Admin scalar query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
        return $fallback;
    }
}

function adminFetchAll(PDO $db, string $sql): array {
    try {
        return $db->query($sql)->fetchAll();
    } catch (Throwable $e) {
        error_log('Admin list query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
        return [];
    }
}

function adminAdsenseActiveAdsTxtLine(string $content): string {
    foreach (preg_split('/\R+/', $content) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (preg_match('/^google\.com,\s*pub-\d+,\s*DIRECT,\s*f08c47fec0942fa0$/i', $line)) {
            return $line;
        }
    }
    return '';
}

function adminAdsenseReadiness(): array {
    $adsTxtPath = __DIR__ . '/../ads.txt';
    $client = defined('ADSENSE_CLIENT') ? ADSENSE_CLIENT : '';
    $adsTxt = is_file($adsTxtPath) ? trim((string)file_get_contents($adsTxtPath)) : '';
    $activeLine = adminAdsenseActiveAdsTxtLine($adsTxt);

    $checks = [
        'Publisher ID configured' => (bool)preg_match('/^ca-pub-\d+$/', $client),
        'ads.txt exists' => is_file($adsTxtPath),
        'ads.txt has active Google line' => $activeLine !== '',
        'Privacy page exists' => is_file(__DIR__ . '/../privacy.php'),
        'About page exists' => is_file(__DIR__ . '/../about.php'),
        'Editorial policy exists' => is_file(__DIR__ . '/../editorial-policy.php'),
        'Contact page exists' => is_file(__DIR__ . '/../contact.php'),
        'Sitemap exists' => is_file(__DIR__ . '/../sitemap.xml.php'),
    ];

    if (!$checks['Publisher ID configured']) {
        $status = 'Missing ID';
    } elseif (!$checks['ads.txt exists'] || !$checks['ads.txt has active Google line']) {
        $status = 'Missing ads.txt';
    } elseif (in_array(false, $checks, true)) {
        $status = 'Needs content pages';
    } else {
        $status = 'Ready for review';
    }

    return [
        'status' => $status,
        'client' => $client,
        'ads_txt' => $adsTxt,
        'active_line' => $activeLine,
        'checks' => $checks,
    ];
}

function adminMediaRel(string $path, string $root): string {
    $rel = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR . '/');
    return str_replace('\\', '/', $rel);
}

function adminCollectMediaRefs(PDO $db): array {
    $refs = [];
    $add = function (?string $value) use (&$refs) {
        $value = trim((string)$value);
        if ($value === '') return;
        if (preg_match_all('#(?:https?://[^"\')\s]+/)?(?:wp-content/)?uploads/([^"\')\s\\\\]+)#i', $value, $m)) {
            foreach ($m[1] as $rel) {
                $refs[ltrim($rel, '/')] = true;
            }
            return;
        }
        if (!preg_match('#^https?://#i', $value)) {
            $refs[ltrim(preg_replace('#^/?uploads/#i', '', $value), '/')] = true;
        }
    };

    foreach (['SELECT avatar FROM users', 'SELECT cover_image FROM boards', 'SELECT image_url FROM board_images', 'SELECT featured_image FROM blog_posts'] as $sql) {
        try {
            foreach ($db->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $value) $add($value);
        } catch (Throwable $e) {}
    }

    try {
        foreach ($db->query('SELECT content FROM blog_posts')->fetchAll(PDO::FETCH_COLUMN) as $content) {
            if (preg_match_all('#(?:https?://[^"\')\s]+/)?(?:wp-content/)?uploads/([^"\')\s\\\\]+)#i', (string)$content, $m)) {
                foreach ($m[1] as $rel) $refs[ltrim($rel, '/')] = true;
            }
        }
    } catch (Throwable $e) {}

    return $refs;
}

function adminScanMedia(string $root, array $refs): array {
    $files = [];
    $totalSize = 0;
    $unusedSize = 0;
    if (!is_dir($root)) return [$files, 0, 0];

    $rit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($rit as $item) {
        if (!$item->isFile()) continue;
        $ext = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) continue;

        $rel = adminMediaRel($item->getPathname(), $root);
        $size = (int)$item->getSize();
        $used = isset($refs[$rel]);
        $totalSize += $size;
        if (!$used) $unusedSize += $size;
        $files[] = [
            'rel' => $rel,
            'url' => '/uploads/' . $rel,
            'size' => $size,
            'mtime' => (int)$item->getMTime(),
            'used' => $used,
        ];
    }

    usort($files, fn($a, $b) => $b['size'] <=> $a['size']);
    return [$files, $totalSize, $unusedSize];
}

function adminMediaSummary(PDO $db, bool $withUsageRefs = true): array {
    $uploadsRoot = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
    $refs = $withUsageRefs ? adminCollectMediaRefs($db) : [];
    [$files, $totalSize, $unusedSize] = adminScanMedia($uploadsRoot, $refs);
    $unusedCount = 0;
    if ($withUsageRefs) {
        foreach ($files as $file) {
            if (!$file['used']) $unusedCount++;
        }
    }
    return [
        'root' => $uploadsRoot,
        'total_count' => count($files),
        'total_size' => $totalSize,
        'unused_count' => $unusedCount,
        'unused_size' => $unusedSize,
    ];
}

function adminBlogPostCount(PDO $db): int {
    try {
        return (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function adminSitemapHealth(PDO $db): array {
    $path = __DIR__ . '/../sitemap.xml.php';
    $postCount = adminBlogPostCount($db);
    return [
        'ready' => is_file($path),
        'label' => is_file($path) ? 'Ready' : 'Missing',
        'post_count' => $postCount,
    ];
}
