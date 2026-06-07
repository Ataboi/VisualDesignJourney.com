<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Board;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class Vdj
{
    /** @var array<string, bool> */
    private static array $columnCache = [];

    public static function siteName(): string
    {
        return config('app.name', 'Visual Design Journey');
    }

    public static function canonical(string $path = '/'): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url('/'.ltrim($path, '/'));
    }

    public static function imageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, '//')) {
            return 'https:'.$path;
        }

        $relative = ltrim($path, '/');

        if (
            ! Str::startsWith($relative, ['uploads/', 'assets/', 'build/', 'storage/', 'wp-content/'])
            && (
                Str::startsWith($relative, ['boards/', 'avatars/', 'blog/', 'media/'])
                || is_file(base_path('uploads/'.$relative))
            )
        ) {
            return asset('uploads/'.$relative);
        }

        return asset($relative);
    }

    public static function boardUrl(Board $board): string
    {
        $slug = $board->slug ?: Str::slug((string) $board->title);

        return url('/board/'.$board->id.($slug ? '-'.$slug : ''));
    }

    public static function blogUrl(BlogPost $post, string $locale = 'en'): string
    {
        $slug = $post->localizedSlug($locale);
        $prefix = match ($locale) {
            'tr' => '/tr/blog/',
            'de' => '/de/blog/',
            default => '/blog/',
        };

        return url($prefix.$slug.'/');
    }

    public static function plainText(?string $value, int $limit = 180): string
    {
        $text = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $value))) ?: '';

        return Str::limit($text, $limit, '...');
    }

    public static function jsonLd(array $data): string
    {
        return json_encode(
            $data,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_PRETTY_PRINT
        );
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            return self::$columnCache[$key] = Schema::hasColumn($table, $column);
        } catch (Throwable) {
            return self::$columnCache[$key] = false;
        }
    }
}
