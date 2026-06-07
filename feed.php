<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$db = getDB();
$type = ($_GET['type'] ?? 'blog') === 'boards' ? 'boards' : 'blog';
$lang = vdj_current_language();
$blogI18nReady = dbColumnExists($db, 'blog_posts', 'title_tr') && dbColumnExists($db, 'blog_posts', 'content_tr');
$boardI18nReady = dbColumnExists($db, 'boards', 'title_tr');

function rssDate(?string $date): string {
    return date(DATE_RSS, strtotime($date ?: 'now'));
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0">
<channel>
  <title><?= htmlspecialchars($type === 'boards' ? 'Visual Design Journey Boards' : 'Visual Design Journey Blog') ?></title>
  <link><?= htmlspecialchars(publicUrl(($lang === 'en' ? '' : '/' . $lang) . ($type === 'boards' ? '/boards-feed.xml' : '/feed.xml'))) ?></link>
  <description><?= htmlspecialchars($type === 'boards' ? __('rss.boards_desc', 'Latest curated mood boards.') : __('rss.blog_desc', 'Latest design articles from Visual Design Journey.')) ?></description>
  <language><?= htmlspecialchars($lang) ?></language>
  <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
<?php
if ($type === 'boards') {
    $stmt = $db->query(
        ($boardI18nReady
        ? 'SELECT b.id, b.slug, b.slug_tr, b.slug_de, b.title, b.title_tr, b.title_de, b.description, b.description_tr, b.description_de, b.cover_image, b.created_at, u.username'
        : 'SELECT b.id, "" AS slug, "" AS slug_tr, "" AS slug_de, b.title, "" AS title_tr, "" AS title_de, b.description, "" AS description_tr, "" AS description_de, b.cover_image, b.created_at, u.username') . '
         FROM boards b JOIN users u ON u.id = b.user_id
         WHERE b.is_draft = 0
         ORDER BY b.created_at DESC LIMIT 50'
    );
    foreach ($stmt->fetchAll() as $row):
        $url = publicUrl(boardUrl($row));
        $rowTitle = localizedBoardTitle($row);
        $rowDesc = localizedBoardDescription($row);
?>
  <item>
    <title><?= htmlspecialchars($rowTitle) ?></title>
    <link><?= htmlspecialchars($url) ?></link>
    <guid isPermaLink="true"><?= htmlspecialchars($url) ?></guid>
    <description><![CDATA[<?= strip_tags($rowDesc ?: 'Curated by ' . $row['username']) ?>]]></description>
    <pubDate><?= rssDate($row['created_at']) ?></pubDate>
  </item>
<?php
    endforeach;
} else {
    try {
        $stmt = $db->query(
            ($blogI18nReady
            ? 'SELECT title, title_tr, title_de, slug, slug_tr, slug_de, excerpt, excerpt_tr, excerpt_de, content, content_tr, content_de, seo_description, seo_description_tr, seo_description_de, published_at'
            : 'SELECT title, "" AS title_tr, "" AS title_de, slug, "" AS slug_tr, "" AS slug_de, excerpt, "" AS excerpt_tr, "" AS excerpt_de, content, "" AS content_tr, "" AS content_de, seo_description, "" AS seo_description_tr, "" AS seo_description_de, published_at') . '
             FROM blog_posts ORDER BY published_at DESC LIMIT 50'
        );
        foreach ($stmt->fetchAll() as $row):
            $url = publicUrl(blogPostUrl($row));
            $rowTitle = localizedField($row, 'title');
            $translation = blogTranslationStatus($row, $lang);
            if ($lang !== 'en' && !$translation['is_translated']) {
                $desc = __('rss.translation_pending_desc', 'This article translation is being prepared.');
            } else {
                $descSource = $lang !== 'en'
                    ? (trim((string)($row['seo_description_' . $lang] ?? '')) ?: trim((string)($row['excerpt_' . $lang] ?? '')))
                    : (trim((string)($row['seo_description'] ?? '')) ?: trim((string)($row['excerpt'] ?? '')));
                $contentSource = $lang !== 'en'
                    ? (string)($row['content_' . $lang] ?? '')
                    : (string)($row['content'] ?? '');
                $desc = cleanBlogExcerpt($rowTitle, $descSource, $contentSource, $lang, 240);
            }
?>
  <item>
    <title><?= htmlspecialchars($rowTitle) ?></title>
    <link><?= htmlspecialchars($url) ?></link>
    <guid isPermaLink="true"><?= htmlspecialchars($url) ?></guid>
    <description><![CDATA[<?= $desc ?>]]></description>
    <pubDate><?= rssDate($row['published_at']) ?></pubDate>
  </item>
<?php
        endforeach;
    } catch (Throwable $e) {}
}
?>
</channel>
</rss>
