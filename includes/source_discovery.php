<?php
/**
 * Source discovery helpers: bookmark URLs, extract Open Graph metadata,
 * and queue visual references for admin moderation.
 */

function vdj_fetch_url(string $url, int $timeout = 8): ?string {
    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
        return null;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'user_agent' => 'VisualDesignJourney/1.0 SourceDiscovery',
            'follow_location' => 1,
            'max_redirects' => 4,
        ],
        'https' => [
            'timeout' => $timeout,
            'user_agent' => 'VisualDesignJourney/1.0 SourceDiscovery',
            'follow_location' => 1,
            'max_redirects' => 4,
        ],
    ]);

    $html = @file_get_contents($url, false, $ctx);
    return is_string($html) && $html !== '' ? $html : null;
}

function vdj_absolute_url(string $maybeUrl, string $baseUrl): string {
    $maybeUrl = trim($maybeUrl);
    if ($maybeUrl === '' || preg_match('#^https?://#i', $maybeUrl)) return $maybeUrl;
    if (str_starts_with($maybeUrl, '//')) {
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        return $scheme . ':' . $maybeUrl;
    }

    $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
    $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';
    if (str_starts_with($maybeUrl, '/')) {
        return $scheme . '://' . $host . $maybeUrl;
    }

    $path = parse_url($baseUrl, PHP_URL_PATH) ?: '/';
    $dir = rtrim(dirname($path), '/');
    return $scheme . '://' . $host . ($dir ? $dir . '/' : '/') . $maybeUrl;
}

function vdj_extract_source_meta(string $url): array {
    $host = parse_url($url, PHP_URL_HOST) ?: 'external';
    $meta = [
        'source_url' => $url,
        'source_platform' => $host,
        'title' => '',
        'description' => '',
        'image_url' => '',
    ];

    $html = vdj_fetch_url($url);
    if ($html === null) {
        return $meta;
    }

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    if (@$doc->loadHTML($html)) {
        $xpath = new DOMXPath($doc);
        $get = function (string $query) use ($xpath): string {
            $node = $xpath->query($query)->item(0);
            if (!$node) return '';
            return trim($node->nodeValue ?: $node->getAttribute('content') ?: $node->getAttribute('href'));
        };

        $meta['title'] = $get("//meta[@property='og:title']/@content")
            ?: $get("//meta[@name='twitter:title']/@content")
            ?: $get('//title');
        $meta['description'] = $get("//meta[@property='og:description']/@content")
            ?: $get("//meta[@name='description']/@content");
        $image = $get("//meta[@property='og:image']/@content")
            ?: $get("//meta[@name='twitter:image']/@content")
            ?: $get("//link[@rel='image_src']/@href")
            ?: $get('//img/@src');
        $meta['image_url'] = $image ? vdj_absolute_url($image, $url) : '';
    }
    libxml_clear_errors();

    return $meta;
}

function vdj_ensure_source_discovery_schema(PDO $db): void {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS source_bookmarks (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS source_feeds (
          id          INT AUTO_INCREMENT PRIMARY KEY,
          name        VARCHAR(160) NOT NULL,
          feed_url    VARCHAR(1000) NOT NULL,
          tags        VARCHAR(255),
          is_active   TINYINT(1) NOT NULL DEFAULT 1,
          last_run_at DATETIME DEFAULT NULL,
          created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uq_feed_url (feed_url(191)),
          KEY idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function vdj_queue_source(PDO $db, array $meta, ?int $userId = null, string $tags = '', string $status = 'pending'): bool {
    if (empty($meta['source_url'])) return false;

    $stmt = $db->prepare(
        'INSERT INTO source_bookmarks
         (user_id, source_url, source_platform, title, description, image_url, suggested_tags, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
           title = IF(VALUES(title) = "", title, VALUES(title)),
           description = IF(VALUES(description) = "", description, VALUES(description)),
           image_url = IF(VALUES(image_url) = "", image_url, VALUES(image_url)),
           suggested_tags = IF(VALUES(suggested_tags) = "", suggested_tags, VALUES(suggested_tags))'
    );
    return $stmt->execute([
        $userId,
        $meta['source_url'],
        $meta['source_platform'] ?? '',
        mb_substr($meta['title'] ?? '', 0, 255),
        mb_substr($meta['description'] ?? '', 0, 500),
        mb_substr($meta['image_url'] ?? '', 0, 1000),
        mb_substr($tags, 0, 255),
        $status,
    ]);
}

/**
 * Build an array of search queries from a board's title, tags, and description.
 * Returns up to 4 distinct query strings for use across different APIs.
 */
function vdj_build_board_queries(array $board): array {
    $queries = [];

    // Primary: board title (clean of common words)
    $stopWords = ['a','an','the','and','or','in','on','at','for','with','by','of','my','our','your'];
    $titleWords = array_filter(
        array_map('strtolower', preg_split('/\s+/', $board['title'] ?? '')),
        fn($w) => strlen($w) > 2 && !in_array($w, $stopWords)
    );
    if ($titleWords) {
        $queries[] = implode(' ', array_slice($titleWords, 0, 4));
    }

    // Secondary: style tags mapped to enriched queries
    $tagQueryMap = [
        'Minimal'       => 'minimalist design clean typography',
        'Bauhaus'       => 'bauhaus modernist geometric design',
        'Y2K'           => 'y2k digital cyber retro futurism',
        'Art Nouveau'   => 'art nouveau organic floral ornamental',
        'Brutalist'     => 'brutalist architecture raw concrete',
        'Dark Academia' => 'dark academia gothic literature moody',
        'Japandi'       => 'japanese scandinavian wabi-sabi minimal',
        'Maximalist'    => 'maximalist eclectic bold pattern decor',
        'Retro'         => 'vintage retro nostalgia mid-century',
        'Swiss'         => 'swiss international style typographic grid',
        'Art Deco'      => 'art deco geometric glamour 1920s',
        'Futurist'      => 'futurism dynamic motion avant-garde',
        'Organic'       => 'organic natural biomorphic form',
        'Grunge'        => 'grunge distressed texture raw aesthetic',
    ];
    $tags = array_map('trim', explode(',', $board['style_tags'] ?? ''));
    foreach ($tags as $tag) {
        foreach ($tagQueryMap as $key => $q) {
            if (stripos($tag, $key) !== false && !in_array($q, $queries)) {
                $queries[] = $q;
                break;
            }
        }
    }

    // Tertiary: description keyword extraction (first 3 meaningful words)
    if (!empty($board['description'])) {
        $descWords = array_filter(
            array_map('strtolower', preg_split('/\s+/', strip_tags($board['description']))),
            fn($w) => strlen($w) > 3 && !in_array($w, $stopWords)
        );
        if ($descWords) {
            $q = implode(' ', array_slice($descWords, 0, 3));
            if (!in_array($q, $queries)) $queries[] = $q;
        }
    }

    return array_slice(array_filter($queries), 0, 4);
}

/**
 * Search Wikimedia Commons for freely licensed images.
 * No API key required. Returns array of ['title','image_url','source_url','description','platform'].
 */
function vdj_search_wikimedia(string $query, int $limit = 20): array {
    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action'       => 'query',
        'generator'    => 'search',
        'gsrnamespace' => 6,
        'gsrsearch'    => $query,
        'prop'         => 'imageinfo',
        'iiprop'       => 'url|size|mime|extmetadata',
        'gsrlimit'     => min($limit, 50),
        'format'       => 'json',
        'origin'       => '*',
    ]);

    $ctx = stream_context_create(['http' => [
        'timeout'    => 10,
        'user_agent' => 'VisualDesignJourney/2.0 (source discovery; non-commercial)',
    ]]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return [];

    $data  = json_decode($json, true);
    $pages = $data['query']['pages'] ?? [];
    $results = [];
    foreach ($pages as $page) {
        $ii = $page['imageinfo'][0] ?? [];
        $mime = $ii['mime'] ?? '';
        if (!str_starts_with($mime, 'image/')) continue;
        // Skip SVG and tiff for display compatibility
        if (in_array($mime, ['image/svg+xml', 'image/tiff'])) continue;

        $ext  = $ii['extmetadata'] ?? [];
        $desc = strip_tags($ext['ImageDescription']['value'] ?? $ext['Artist']['value'] ?? '');
        $license = $ext['LicenseShortName']['value'] ?? 'CC';

        $results[] = [
            'title'       => $page['title'] ?? '',
            'image_url'   => $ii['url'] ?? '',
            'source_url'  => $ii['descriptionurl'] ?? ('https://commons.wikimedia.org/wiki/' . ($page['title'] ?? '')),
            'description' => mb_substr($desc, 0, 300) . ($license ? " [{$license}]" : ''),
            'platform'    => 'Wikimedia Commons',
            'license'     => $license,
        ];
    }
    return $results;
}

/**
 * Search the Met Museum Open Access collection (public domain).
 * No API key required.
 */
function vdj_search_met_museum(string $query, int $limit = 20): array {
    $searchUrl = 'https://collectionapi.metmuseum.org/public/collection/v1/search?' . http_build_query([
        'hasImages' => 'true',
        'isPublicDomain' => 'true',
        'q' => $query,
    ]);
    $ctx = stream_context_create(['http' => [
        'timeout'    => 10,
        'user_agent' => 'VisualDesignJourney/2.0 (educational; metmuseum.org/terms)',
    ]]);
    $json = @file_get_contents($searchUrl, false, $ctx);
    if (!$json) return [];

    $data = json_decode($json, true);
    $objectIds = array_slice($data['objectIDs'] ?? [], 0, min($limit, 20));
    if (!$objectIds) return [];

    $results = [];
    foreach ($objectIds as $objId) {
        $objUrl  = "https://collectionapi.metmuseum.org/public/collection/v1/objects/{$objId}";
        $objJson = @file_get_contents($objUrl, false, $ctx);
        if (!$objJson) continue;
        $obj = json_decode($objJson, true);
        if (empty($obj['primaryImage'])) continue;

        $results[] = [
            'title'       => $obj['title'] ?? 'Met Museum',
            'image_url'   => $obj['primaryImage'],
            'source_url'  => $obj['objectURL'] ?? "https://www.metmuseum.org/art/collection/search/{$objId}",
            'description' => trim(($obj['artistDisplayName'] ?? '') . ' ' . ($obj['objectDate'] ?? '') . ' — ' . ($obj['medium'] ?? '')),
            'platform'    => 'Met Museum',
            'license'     => 'Public Domain',
        ];
        if (count($results) >= $limit) break;
        usleep(50000); // 50ms between requests, be polite
    }
    return $results;
}

/**
 * Search the Art Institute of Chicago Open Access collection (public domain).
 * No API key required.
 */
function vdj_search_chicago_art(string $query, int $limit = 16): array {
    $url = 'https://api.artic.edu/api/v1/artworks/search?' . http_build_query([
        'q'      => $query,
        'limit'  => min($limit, 20),
        'fields' => 'id,title,artist_display,image_id,description,style_title,medium_display',
    ]);
    $ctx = stream_context_create(['http' => [
        'timeout'    => 10,
        'user_agent' => 'VisualDesignJourney/2.0 (source discovery; api.artic.edu)',
    ]]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return [];

    $data = json_decode($json, true);
    $artworks = $data['data'] ?? [];
    $results = [];
    foreach ($artworks as $art) {
        if (empty($art['image_id'])) continue;
        $imageUrl = "https://www.artic.edu/iiif/2/{$art['image_id']}/full/843,/0/default.jpg";
        $results[] = [
            'title'       => $art['title'] ?? '',
            'image_url'   => $imageUrl,
            'source_url'  => "https://www.artic.edu/artworks/{$art['id']}",
            'description' => mb_substr(trim(strip_tags($art['description'] ?? $art['artist_display'] ?? '')), 0, 300),
            'platform'    => 'Art Institute of Chicago',
            'license'     => 'Public Domain',
        ];
    }
    return $results;
}
