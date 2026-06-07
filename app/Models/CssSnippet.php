<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CssSnippet extends Model
{
    protected $fillable = [
        'name', 'slug', 'snippet_type', 'description',
        'preview_css', 'preview_html', 'code', 'language',
        'tags', 'style', 'is_active', 'is_featured', 'view_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public static array $typeLabels = [
        'gradient'        => 'Gradient Library',
        'shadow'          => 'Shadow Styles',
        'glassmorphism'   => 'Glassmorphism Presets',
        'motion'          => 'Motion Presets',
        'easing'          => 'Transition Easing',
        'clip_path'       => 'Clip-Path Gallery',
        'svg_pattern'     => 'SVG Patterns',
        'noise'           => 'Noise Textures',
        'micro_interaction' => 'Micro-interactions',
        'css_variable'    => 'CSS Variable Kits',
        'tailwind_config' => 'Tailwind Config Snippets',
        'container_query' => 'Container Query Snippets',
        'css_reset'       => 'CSS Reset Comparison',
        'print_style'     => 'Print Style Reference',
    ];

    public static array $typeRoutes = [
        'gradient'        => 'gradients',
        'shadow'          => 'shadows',
        'glassmorphism'   => 'glassmorphism',
        'motion'          => 'motion-presets',
        'easing'          => 'easing-reference',
        'clip_path'       => 'clip-paths',
        'svg_pattern'     => 'svg-patterns',
        'noise'           => 'noise-textures',
        'micro_interaction' => 'micro-interactions',
        'css_variable'    => 'css-variables',
        'tailwind_config' => 'tailwind-snippets',
        'container_query' => 'container-queries',
        'css_reset'       => 'css-resets',
        'print_style'     => 'print-styles',
    ];
}
