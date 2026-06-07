#!/usr/bin/env python3
"""
blog-import.py
WordPress XML → MySQL SQL dump for blog_posts, blog_categories, blog_tags, etc.

Usage:
    python3 blog-import.py

Output:
    /Users/ataozelbicer/Desktop/blog-import.sql
"""

import re
import sys
import xml.etree.ElementTree as ET
from datetime import datetime

# ── Config ────────────────────────────────────────────────────────────────────
XML_PATH = '/Users/ataozelbicer/Downloads/visualdesignjourney.WordPress.2026-05-18.xml'
OUT_PATH = '/Users/ataozelbicer/Desktop/blog-import.sql'

# ── Namespaces ────────────────────────────────────────────────────────────────
NS = {
    'content': 'http://purl.org/rss/1.0/modules/content/',
    'dc':      'http://purl.org/dc/elements/1.1/',
    'excerpt': 'http://wordpress.org/export/1.2/excerpt/',
    'wp':      'http://wordpress.org/export/1.2/',
}

# ── Helpers ───────────────────────────────────────────────────────────────────
def esc(value: str) -> str:
    """Escape a string for MySQL single-quoted string literals."""
    if value is None:
        return ''
    value = str(value)
    value = value.replace('\\', '\\\\')
    value = value.replace("'",  "\\'")
    value = value.replace('\x00', '')
    return value

def strip_gutenberg(html: str) -> str:
    """Remove <!-- wp:* --> and <!-- /wp:* --> Gutenberg block comments."""
    html = re.sub(r'<!--\s*/?\s*wp:[^\-]*?-->', '', html, flags=re.DOTALL)
    return html.strip()

def strip_html_tags(html: str) -> str:
    """Strip all HTML tags from a string."""
    return re.sub(r'<[^>]+>', '', html)

def normalize_media_url(url: str) -> str:
    """Make imported WordPress media URLs portable across domains."""
    if not url:
        return ''
    match = re.search(r'https?://[^"\')\s]+/(wp-content/uploads/[^"\')\s]+)', url, flags=re.I)
    if match:
        return '/' + match.group(1)
    return url

def normalize_content_media(html: str) -> str:
    """Rewrite absolute wp-content media URLs inside post content to root-relative URLs."""
    if not html:
        return ''
    return re.sub(
        r'https?://[^"\')\s]+/(wp-content/uploads/[^"\')\s]+)',
        r'/\1',
        html,
        flags=re.I,
    )

def make_excerpt(content: str, max_chars: int = 300) -> str:
    """Strip Gutenberg comments, strip HTML, truncate."""
    text = strip_gutenberg(content)
    text = strip_html_tags(text)
    text = re.sub(r'\s+', ' ', text).strip()
    if len(text) > max_chars:
        text = text[:max_chars].rsplit(' ', 1)[0] + '…'
    return text

def tag(el, name: str) -> str:
    """Get text content of a direct child element by name."""
    child = el.find(name)
    if child is None:
        return ''
    return (child.text or '').strip()

def nstag(el, ns_key: str, local: str) -> str:
    """Get text content of a namespaced child element."""
    child = el.find(f'{{{NS[ns_key]}}}{local}')
    if child is None:
        return ''
    return (child.text or '').strip()

def parse_date(raw: str) -> str:
    """Convert WP post_date to MySQL DATETIME."""
    raw = raw.strip()
    if not raw or raw == '0000-00-00 00:00:00':
        return datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')
    try:
        dt = datetime.strptime(raw, '%Y-%m-%d %H:%M:%S')
        return dt.strftime('%Y-%m-%d %H:%M:%S')
    except ValueError:
        pass
    try:
        # Try pubDate format: Mon, 01 Jan 2023 12:00:00 +0000
        from email.utils import parsedate_to_datetime
        dt = parsedate_to_datetime(raw)
        return dt.strftime('%Y-%m-%d %H:%M:%S')
    except Exception:
        return datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S')

# ── Parse XML ────────────────────────────────────────────────────────────────
print(f"Parsing {XML_PATH} …")
try:
    tree = ET.parse(XML_PATH)
except ET.ParseError as exc:
    print(f"ERROR: Could not parse XML — {exc}")
    sys.exit(1)

root    = tree.getroot()
channel = root.find('channel')
if channel is None:
    print("ERROR: No <channel> element found")
    sys.exit(1)

items = channel.findall('item')
print(f"Found {len(items)} total items")

# ── Build attachment ID → URL map ─────────────────────────────────────────────
attachment_map = {}  # wp_id (str) → url (str)
for item in items:
    post_type = nstag(item, 'wp', 'post_type')
    if post_type != 'attachment':
        continue
    wp_id = nstag(item, 'wp', 'post_id')
    guid  = tag(item, 'guid')
    if wp_id and guid:
        attachment_map[wp_id] = guid

print(f"Attachment map: {len(attachment_map)} entries")

# ── Collect categories (from channel-level <wp:category>) ─────────────────────
# slug → {name, description}
channel_cats = {}
for wc in channel.findall(f'{{{NS["wp"]}}}category'):
    slug = nstag(wc, 'wp', 'category_nicename')
    name = nstag(wc, 'wp', 'cat_name')
    desc_el = wc.find(f'{{{NS["wp"]}}}category_description')
    desc = (desc_el.text or '').strip() if desc_el is not None else ''
    if slug:
        channel_cats[slug] = {'name': name, 'description': desc}

# ── Collect tags (from channel-level <wp:tag>) ────────────────────────────────
channel_tags = {}
for wt in channel.findall(f'{{{NS["wp"]}}}tag'):
    slug = nstag(wt, 'wp', 'tag_slug')
    name = nstag(wt, 'wp', 'tag_name')
    if slug:
        channel_tags[slug] = name

# ── Process published posts ───────────────────────────────────────────────────
posts_data      = []  # list of dicts
all_cat_slugs   = {}  # slug → {name, description}
all_tag_slugs   = {}  # slug → name

post_count      = 0
skip_count      = 0
with_image      = 0
without_image   = 0

for item in items:
    post_type   = nstag(item, 'wp', 'post_type')
    post_status = nstag(item, 'wp', 'status')

    if post_type != 'post' or post_status != 'publish':
        skip_count += 1
        continue

    post_count += 1
    wp_id  = nstag(item, 'wp', 'post_id')
    title  = tag(item, 'title')
    slug   = nstag(item, 'wp', 'post_name')
    author = nstag(item, 'dc', 'creator')
    date_raw = nstag(item, 'wp', 'post_date')
    pub_date = parse_date(date_raw)

    # Content
    content_raw = nstag(item, 'content', 'encoded')
    content     = normalize_content_media(strip_gutenberg(content_raw))

    # Excerpt
    excerpt_raw = nstag(item, 'excerpt', 'encoded')
    if excerpt_raw.strip():
        excerpt = make_excerpt(excerpt_raw, 300)
    else:
        excerpt = make_excerpt(content_raw, 300)

    # Meta fields
    meta = {}
    for pm in item.findall(f'{{{NS["wp"]}}}postmeta'):
        mk = nstag(pm, 'wp', 'meta_key')
        mv = nstag(pm, 'wp', 'meta_value')
        if mk:
            meta[mk] = mv

    # Featured image
    featured_image = ''
    thumb_id = meta.get('_thumbnail_id', '').strip()
    if thumb_id and thumb_id in attachment_map:
        url = attachment_map[thumb_id]
        if url.startswith('http'):
            featured_image = normalize_media_url(url)

    if featured_image:
        with_image += 1
    else:
        without_image += 1

    # SEO description (priority order)
    seo_description = ''
    for key in ('_aioseo_description', '_wpaicg_meta_description', '_aioseo_og_description'):
        val = meta.get(key, '').strip()
        if val:
            seo_description = val[:300]
            break

    # SEO title (priority order)
    seo_title = ''
    for key in ('_aioseo_title', '_wpaicg_meta_title'):
        val = meta.get(key, '').strip()
        if val:
            seo_title = val[:200]
            break

    # Categories and tags from <category> elements
    post_cats = []  # list of slugs
    post_tags = []  # list of slugs

    for cat_el in item.findall('category'):
        domain = cat_el.get('domain', '')
        cat_slug = cat_el.get('nicename', '')
        cat_name = (cat_el.text or '').strip()
        if not cat_slug:
            continue
        if domain == 'category':
            post_cats.append(cat_slug)
            if cat_slug not in all_cat_slugs:
                # Use channel-level description if available
                desc = channel_cats.get(cat_slug, {}).get('description', '')
                name_from_channel = channel_cats.get(cat_slug, {}).get('name', cat_name)
                all_cat_slugs[cat_slug] = {'name': name_from_channel, 'description': desc}
        elif domain == 'post_tag':
            post_tags.append(cat_slug)
            if cat_slug not in all_tag_slugs:
                name_from_channel = channel_tags.get(cat_slug, cat_name)
                all_tag_slugs[cat_slug] = name_from_channel

    posts_data.append({
        'wp_id':           wp_id,
        'title':           title,
        'slug':            slug,
        'content':         content,
        'excerpt':         excerpt,
        'featured_image':  featured_image,
        'author':          author,
        'seo_title':       seo_title,
        'seo_description': seo_description,
        'published_at':    pub_date,
        'cats':            post_cats,
        'tags':            post_tags,
    })

    if post_count % 200 == 0:
        print(f"  Processed {post_count} posts…")

print(f"\nTotal published posts: {post_count}")
print(f"Skipped items (non-post/draft): {skip_count}")
print(f"Unique categories: {len(all_cat_slugs)}")
print(f"Unique tags:       {len(all_tag_slugs)}")
print(f"Posts with image:  {with_image}")
print(f"Posts without img: {without_image}")

# ── Generate SQL ──────────────────────────────────────────────────────────────
print(f"\nWriting SQL to {OUT_PATH} …")

lines = []
lines.append('-- blog-import.sql')
lines.append(f'-- Generated: {datetime.utcnow().isoformat()} UTC')
lines.append(f'-- Posts: {post_count}, Categories: {len(all_cat_slugs)}, Tags: {len(all_tag_slugs)}')
lines.append('')
lines.append('SET NAMES utf8mb4;')
lines.append('SET foreign_key_checks = 0;')
lines.append('')

# ── blog_categories ──
lines.append('-- blog_categories')
for slug, info in all_cat_slugs.items():
    name = esc(info['name'])
    desc = esc(info['description'])
    sl   = esc(slug)
    lines.append(
        f"INSERT IGNORE INTO `blog_categories` (`name`, `slug`, `description`) "
        f"VALUES ('{name}', '{sl}', '{desc}');"
    )
lines.append('')

# ── blog_tags ──
lines.append('-- blog_tags')
for slug, name in all_tag_slugs.items():
    n  = esc(name)
    sl = esc(slug)
    lines.append(
        f"INSERT IGNORE INTO `blog_tags` (`name`, `slug`) "
        f"VALUES ('{n}', '{sl}');"
    )
lines.append('')

# ── blog_posts + pivot tables ──
lines.append('-- blog_posts')
for idx, p in enumerate(posts_data):
    title   = esc(p['title'])
    slug    = esc(p['slug'])
    content = esc(p['content'])
    excerpt = esc(p['excerpt'])
    fimg    = esc(p['featured_image'])
    author  = esc(p['author'])
    st      = esc(p['seo_title'])
    sd      = esc(p['seo_description'])
    pub     = esc(p['published_at'])
    wp_id   = int(p['wp_id']) if p['wp_id'].isdigit() else 0

    lines.append(
        f"INSERT IGNORE INTO `blog_posts` "
        f"(`wp_id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, "
        f"`author`, `seo_title`, `seo_description`, `published_at`) "
        f"VALUES ({wp_id}, '{title}', '{slug}', '{content}', '{excerpt}', "
        f"'{fimg}', '{author}', '{st}', '{sd}', '{pub}');"
    )

lines.append('')

# ── blog_post_categories (using subqueries to resolve IDs) ──
lines.append('-- blog_post_categories')
for p in posts_data:
    slug = esc(p['slug'])
    for cat_slug in p['cats']:
        cs = esc(cat_slug)
        lines.append(
            f"INSERT IGNORE INTO `blog_post_categories` (`post_id`, `category_id`) "
            f"SELECT p.id, c.id FROM `blog_posts` p, `blog_categories` c "
            f"WHERE p.slug = '{slug}' AND c.slug = '{cs}';"
        )

lines.append('')

# ── blog_post_tags (using subqueries) ──
lines.append('-- blog_post_tags')
for p in posts_data:
    slug = esc(p['slug'])
    for tag_slug in p['tags']:
        ts = esc(tag_slug)
        lines.append(
            f"INSERT IGNORE INTO `blog_post_tags` (`post_id`, `tag_id`) "
            f"SELECT p.id, t.id FROM `blog_posts` p, `blog_tags` t "
            f"WHERE p.slug = '{slug}' AND t.slug = '{ts}';"
        )

lines.append('')
lines.append('SET foreign_key_checks = 1;')
lines.append('')

with open(OUT_PATH, 'w', encoding='utf-8') as f:
    f.write('\n'.join(lines))

print(f"Done! SQL written to {OUT_PATH}")
print(f"\nSummary:")
print(f"  Posts imported:    {post_count}")
print(f"  Categories:        {len(all_cat_slugs)}")
print(f"  Tags:              {len(all_tag_slugs)}")
print(f"  With featured img: {with_image}")
print(f"  Without img:       {without_image}")
