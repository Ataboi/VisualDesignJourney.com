<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColorReference extends Model
{
    protected $fillable = [
        'name', 'slug', 'reference_type', 'description',
        'colors', 'css_code', 'tailwind_code', 'meta', 'style',
        'is_active', 'is_featured', 'view_count',
    ];

    protected $casts = [
        'colors' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public static array $typeLabels = [
        'color_role'      => 'UI Color Roles Guide',
        'mesh_gradient'   => 'Mesh Gradient Gallery',
        'bg_pattern'      => 'CSS Background Patterns',
        'accessible_pair' => 'Accessible Color Pairs',
        'brand_archetype' => 'Brand Archetype Explorer',
        'color_story'     => 'Color Story Generator',
    ];

    public static array $typeRoutes = [
        'color_role'      => 'color-roles',
        'mesh_gradient'   => 'mesh-gradients',
        'bg_pattern'      => 'background-patterns',
        'accessible_pair' => 'accessible-colors',
        'brand_archetype' => 'brand-archetypes',
        'color_story'     => 'color-stories',
    ];
}
