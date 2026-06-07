<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function legacy(Request $request, string $path = '')
    {
        $path = trim($path ?: (string) ($request->query('path') ?: $request->query('src', '')));
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if($path === '' || str_contains($path, '..'), 404);

        foreach ([base_path('uploads/'.$path), base_path('wp-content/uploads/'.$path)] as $file) {
            if (is_file($file)) {
                return response()->file($file, [
                    'Cache-Control' => 'public, max-age=31536000, immutable',
                ]);
            }
        }

        $label = Str::headline(pathinfo($path, PATHINFO_FILENAME) ?: 'Visual Reference');
        $safeLabel = e(Str::limit($label, 58, ''));
        $safePath = e($path);
        $palettes = [
            ['#FAF8FF', '#7F77DD', '#131B2E'],
            ['#F8F9FA', '#574EB1', '#131B2E'],
            ['#F2F3FF', '#7F77DD', '#131B2E'],
        ];
        [$bg, $accent, $text] = $palettes[abs(crc32($path)) % count($palettes)];

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-label="{$safeLabel}">
  <rect width="1200" height="800" fill="{$bg}"/>
  <rect x="72" y="72" width="1056" height="656" rx="28" fill="#FFFFFF" stroke="#E9ECEF" stroke-width="1"/>
  <circle cx="134" cy="128" r="8" fill="{$accent}"/>
  <path d="M120 610 L342 388 L486 532 L602 416 L894 708 H120 Z" fill="{$accent}" opacity=".16"/>
  <text x="158" y="138" fill="#787583" font-family="Inter, Arial, sans-serif" font-size="24" font-weight="700">Visual Design Journey</text>
  <text x="92" y="665" fill="{$text}" font-family="Manrope, Inter, Arial, sans-serif" font-size="48" font-weight="700">{$safeLabel}</text>
  <text x="92" y="710" fill="#454F5E" font-family="Inter, Arial, sans-serif" font-size="22">Archived reference placeholder</text>
  <desc>Missing legacy media path: {$safePath}</desc>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
