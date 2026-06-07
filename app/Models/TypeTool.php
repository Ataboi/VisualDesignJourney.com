<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeTool extends Model
{
    protected $fillable = [
        'name', 'slug', 'tool_type', 'description',
        'values', 'css_snippet', 'tailwind_snippet',
        'base_value', 'ratio', 'use_case',
        'is_active', 'is_featured', 'view_count',
    ];

    protected $casts = [
        'values' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public static array $typeLabels = [
        'type_scale'    => 'Type Scale Generator',
        'line_height'   => 'Line Height Reference',
        'letter_spacing'=> 'Letter Spacing Reference',
        'font_clamp'    => 'Font Size Clamp Generator',
        'border_radius' => 'Border Radius Scale',
        'spacing_scale' => 'Spacing Scale',
        'z_index'       => 'Z-Index Manager',
        'breakpoint'    => 'Breakpoint Reference',
        'aspect_ratio'  => 'Aspect Ratio Reference',
        'viewport_unit' => 'Viewport Unit Cheatsheet',
        'grid_ref'      => 'CSS Grid Cheatsheet',
        'flexbox_ref'   => 'Flexbox Cheatsheet',
    ];

    public static array $typeRoutes = [
        'type_scale'    => 'type-scales',
        'line_height'   => 'line-heights',
        'letter_spacing'=> 'letter-spacing',
        'font_clamp'    => 'font-clamps',
        'border_radius' => 'border-radius',
        'spacing_scale' => 'spacing-scale',
        'z_index'       => 'z-index',
        'breakpoint'    => 'breakpoints',
        'aspect_ratio'  => 'aspect-ratios',
        'viewport_unit' => 'viewport-units',
        'grid_ref'      => 'css-grid',
        'flexbox_ref'   => 'flexbox',
    ];
}
