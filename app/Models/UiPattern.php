<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UiPattern extends Model
{
    protected $fillable = [
        'name', 'slug', 'pattern_type', 'description',
        'preview_html', 'code', 'language', 'style', 'framework',
        'tags', 'is_active', 'is_featured', 'view_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public static array $typeLabels = [
        'button'       => 'Button Style Gallery',
        'form_field'   => 'Form Field Gallery',
        'card'         => 'Card Style Gallery',
        'navigation'   => 'Navigation Patterns',
        'hero'         => 'Hero Section Templates',
        'cta'          => 'CTA Section Library',
        'footer'       => 'Footer Templates',
        'pricing'      => 'Pricing Table Gallery',
        'testimonial'  => 'Testimonial Blocks',
        'feature'      => 'Feature Section Gallery',
        'empty_state'  => 'Empty State Library',
        'loading'      => 'Loading State Library',
        'error_page'   => 'Error Page Gallery',
        'toast'        => 'Toast & Notification Gallery',
        'modal'        => 'Modal & Dialog Gallery',
        'sidebar'      => 'Sidebar Layout Gallery',
        'dashboard'    => 'Dashboard Layout Gallery',
        'mobile'       => 'Mobile UI Patterns',
        'dark_ui'      => 'Dark UI Inspiration',
        'email'        => 'Email Template Gallery',
        'og_template'  => 'Open Graph Templates',
        'icon_style'   => 'Icon Style Guide',
        'illustration' => 'Illustration Style Guide',
        'photography'  => 'Photography Direction Guide',
    ];

    public static array $typeRoutes = [
        'button'       => 'buttons',
        'form_field'   => 'form-fields',
        'card'         => 'card-styles',
        'navigation'   => 'navigation-patterns',
        'hero'         => 'hero-templates',
        'cta'          => 'cta-library',
        'footer'       => 'footer-templates',
        'pricing'      => 'pricing-tables',
        'testimonial'  => 'testimonial-blocks',
        'feature'      => 'feature-sections',
        'empty_state'  => 'empty-states',
        'loading'      => 'loading-states',
        'error_page'   => 'error-pages',
        'toast'        => 'toast-gallery',
        'modal'        => 'modal-gallery',
        'sidebar'      => 'sidebar-layouts',
        'dashboard'    => 'dashboard-layouts',
        'mobile'       => 'mobile-patterns',
        'dark_ui'      => 'dark-ui',
        'email'        => 'email-templates',
        'og_template'  => 'og-templates',
        'icon_style'   => 'icon-styles',
        'illustration' => 'illustration-styles',
        'photography'  => 'photography-guide',
    ];
}
