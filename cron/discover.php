<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/source_discovery.php';

header('Content-Type: text/plain; charset=utf-8');

$db = getDB();
vdj_ensure_source_discovery_schema($db);
$added = 0;

try {
    $feeds = $db->query('SELECT * FROM source_feeds WHERE is_active = 1 ORDER BY last_run_at ASC, id ASC LIMIT 10')->fetchAll();
} catch (Throwable $e) {
    http_response_code(500);
    echo "Run schema-update.sql first.\n";
    exit;
}

foreach ($feeds as $feed) {
    $xmlRaw = vdj_fetch_url($feed['feed_url'], 10);
    if ($xmlRaw === null) continue;

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlRaw, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$xml) continue;

    $items = [];
    if (isset($xml->channel->item)) {
        $items = $xml->channel->item;
    } elseif (isset($xml->entry)) {
        $items = $xml->entry;
    }

    $count = 0;
    foreach ($items as $item) {
        if ($count >= 8) break;
        $link = '';
        if (isset($item->link)) {
            $link = (string)$item->link;
            if ($link === '' && isset($item->link['href'])) $link = (string)$item->link['href'];
        }
        if ($link === '') continue;

        $meta = vdj_extract_source_meta($link);
        if (empty($meta['image_url'])) {
            $namespaces = $item->getNamespaces(true);
            if (isset($namespaces['media'])) {
                $media = $item->children($namespaces['media']);
                if (isset($media->content[0]['url'])) $meta['image_url'] = (string)$media->content[0]['url'];
                if (isset($media->thumbnail[0]['url'])) $meta['image_url'] = (string)$media->thumbnail[0]['url'];
            }
        }
        if (($meta['title'] ?? '') === '' && isset($item->title)) $meta['title'] = (string)$item->title;
        if (($meta['description'] ?? '') === '' && isset($item->description)) $meta['description'] = strip_tags((string)$item->description);

        if (vdj_queue_source($db, $meta, null, $feed['tags'] ?? '')) {
            $added++;
            $count++;
        }
    }

    $stmt = $db->prepare('UPDATE source_feeds SET last_run_at = NOW() WHERE id = ?');
    $stmt->execute([(int)$feed['id']]);
}

echo "Discovery complete. Queued/updated references: {$added}\n";
