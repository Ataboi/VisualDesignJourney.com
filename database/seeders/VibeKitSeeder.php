<?php

namespace Database\Seeders;

use App\Models\BriefTemplate;
use App\Models\Category;
use App\Models\ColorPalette;
use App\Models\DesignPrompt;
use App\Models\FontPairing;
use App\Models\Tag;
use App\Models\WebsiteSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VibeKitSeeder extends Seeder
{
    public function run(): void
    {
        // Tags
        $tags = collect([
            'editorial', 'minimal', 'bold', 'dark', 'elegant', 'modern', 'branding',
            'web', 'print', 'ui', 'motion', 'illustration',
        ])->map(fn ($name) => Tag::create(['name' => $name, 'slug' => $name, 'is_active' => true]));

        // Categories
        $fontCat = Category::create(['name' => 'Serif & Sans', 'slug' => 'serif-sans', 'type' => 'font', 'is_active' => true]);
        $colorCat = Category::create(['name' => 'Dark Palettes', 'slug' => 'dark-palettes', 'type' => 'color', 'is_active' => true]);
        $promptCat = Category::create(['name' => 'UI Design', 'slug' => 'ui-design', 'type' => 'prompt', 'is_active' => true]);
        $briefCat = Category::create(['name' => 'Landing Pages', 'slug' => 'landing-pages', 'type' => 'brief', 'is_active' => true]);

        // Font Pairings
        $fontData = [
            ['name' => 'Playfair + Inter', 'heading_font' => 'Playfair Display', 'body_font' => 'Inter', 'heading_weight' => '700', 'body_weight' => '400', 'style' => 'editorial', 'preview_text' => 'Design with intention.', 'is_featured' => true],
            ['name' => 'Syne + DM Sans', 'heading_font' => 'Syne', 'body_font' => 'DM Sans', 'heading_weight' => '800', 'body_weight' => '400', 'style' => 'bold', 'preview_text' => 'Bold moves.', 'is_featured' => true],
            ['name' => 'Cormorant + Lato', 'heading_font' => 'Cormorant Garamond', 'body_font' => 'Lato', 'heading_weight' => '600', 'body_weight' => '300', 'style' => 'elegant', 'preview_text' => 'Timeless elegance.', 'is_featured' => true],
            ['name' => 'Space Grotesk + Source Sans', 'heading_font' => 'Space Grotesk', 'body_font' => 'Source Sans 3', 'heading_weight' => '700', 'body_weight' => '400', 'style' => 'modern', 'preview_text' => 'Built for the web.', 'is_featured' => true],
            ['name' => 'Fraunces + Nunito', 'heading_font' => 'Fraunces', 'body_font' => 'Nunito', 'heading_weight' => '700', 'body_weight' => '400', 'style' => 'editorial', 'preview_text' => 'Warm and inviting.', 'is_featured' => false],
            ['name' => 'Bebas + Open Sans', 'heading_font' => 'Bebas Neue', 'body_font' => 'Open Sans', 'heading_weight' => '400', 'body_weight' => '400', 'style' => 'bold', 'preview_text' => 'COMMAND ATTENTION.', 'is_featured' => false],
        ];

        foreach ($fontData as $data) {
            FontPairing::create(array_merge($data, [
                'slug' => Str::slug($data['name']),
                'category_id' => $fontCat->id,
                'is_active' => true,
                'view_count' => rand(10, 500),
                'description' => 'A carefully selected font pairing that creates strong visual hierarchy.',
            ]));
        }

        // Color Palettes
        $paletteData = [
            ['name' => 'Midnight Violet', 'colors' => ['#0a0a0f', '#111118', '#1e1e2a', '#7c3aed', '#a78bfa'], 'mood' => 'dark', 'is_featured' => true],
            ['name' => 'Deep Ocean', 'colors' => ['#0d1b2a', '#1b2838', '#1f4068', '#1b6ca8', '#63b3ed'], 'mood' => 'dark', 'is_featured' => true],
            ['name' => 'Neon Noir', 'colors' => ['#0a0010', '#1a0030', '#ff006e', '#fb5607', '#ffbe0b'], 'mood' => 'neon', 'is_featured' => true],
            ['name' => 'Sage Garden', 'colors' => ['#f5f0eb', '#d4c9b0', '#8b9d77', '#4a6741', '#2d4a25'], 'mood' => 'earthy', 'is_featured' => true],
            ['name' => 'Cotton Candy', 'colors' => ['#fff0f3', '#ffb3c1', '#ff85a1', '#fb6f92', '#ff4d6d'], 'mood' => 'pastel', 'is_featured' => true],
            ['name' => 'Zinc Studio', 'colors' => ['#09090b', '#18181b', '#27272a', '#3f3f46', '#71717a'], 'mood' => 'monochrome', 'is_featured' => true],
            ['name' => 'Solar Flare', 'colors' => ['#0a0a0a', '#1a0a00', '#ff6b00', '#ff9500', '#ffcc00'], 'mood' => 'vibrant', 'is_featured' => false],
            ['name' => 'Arctic Mist', 'colors' => ['#f0f4f8', '#d9e2ec', '#9fb3c8', '#627d98', '#334e68'], 'mood' => 'light', 'is_featured' => false],
        ];

        foreach ($paletteData as $data) {
            ColorPalette::create(array_merge($data, [
                'slug' => Str::slug($data['name']),
                'category_id' => $colorCat->id,
                'is_active' => true,
                'view_count' => rand(20, 800),
            ]));
        }

        // Design Prompts
        $promptData = [
            ['title' => 'Design a dark-mode SaaS dashboard', 'type' => 'ui', 'difficulty' => 'medium', 'tool' => 'Figma', 'is_featured' => true, 'prompt' => 'Design a dashboard for a SaaS analytics product in dark mode. Include a sidebar navigation, stat cards, a line chart, and a recent activity feed. Focus on information density and visual clarity.'],
            ['title' => 'Create a magazine-style editorial layout', 'type' => 'typography', 'difficulty' => 'hard', 'tool' => 'Any', 'is_featured' => true, 'prompt' => 'Design a two-page editorial spread for a design magazine. Feature a large hero image, a drop cap, pull quotes, and a structured body text layout. Use bold typography and strong grid alignment.'],
            ['title' => 'Design a mobile onboarding flow', 'type' => 'ui', 'difficulty' => 'easy', 'tool' => 'Figma', 'is_featured' => true, 'prompt' => 'Create a 3-screen mobile onboarding experience for a meditation app. Each screen should introduce one key feature. Use calm colors, simple illustrations, and clear CTAs.'],
            ['title' => 'Brand identity for an indie coffee roaster', 'type' => 'branding', 'difficulty' => 'hard', 'tool' => 'Illustrator', 'is_featured' => false, 'prompt' => 'Design a complete brand identity package for a specialty coffee roaster. Include a primary logo, wordmark, brand mark, color palette, and two application mockups (packaging and tote bag).'],
            ['title' => 'Redesign a product pricing page', 'type' => 'ui', 'difficulty' => 'medium', 'tool' => 'Any', 'is_featured' => false, 'prompt' => 'Redesign a pricing page for a SaaS product with three tiers: Free, Pro, and Enterprise. Make the middle tier stand out. Include feature lists, a toggle for monthly/annual billing, and a FAQ section.'],
            ['title' => 'Design a minimal poster for a type specimen', 'type' => 'typography', 'difficulty' => 'easy', 'tool' => 'Any', 'is_featured' => false, 'prompt' => 'Create a minimal A3 poster that showcases a single typeface. Display the full alphabet, numerals, weights, and a short pangram. Let the type be the hero.'],
        ];

        foreach ($promptData as $data) {
            DesignPrompt::create(array_merge($data, [
                'slug' => Str::slug($data['title']),
                'category_id' => $promptCat->id,
                'is_active' => true,
                'view_count' => rand(5, 300),
            ]));
        }

        // Brief Template
        BriefTemplate::create([
            'name' => 'Landing Page Brief',
            'slug' => 'landing-page-brief',
            'industry' => 'Tech / SaaS',
            'category_id' => $briefCat->id,
            'is_featured' => true,
            'is_active' => true,
            'description' => 'A structured brief to kick off any landing page design project.',
            'fields' => [
                ['label' => 'Product Name', 'key' => 'product_name', 'type' => 'text', 'placeholder' => 'e.g. Acme Analytics'],
                ['label' => 'Target Audience', 'key' => 'target_audience', 'type' => 'text', 'placeholder' => 'e.g. SaaS founders, indie hackers'],
                ['label' => 'Primary Goal', 'key' => 'primary_goal', 'type' => 'text', 'placeholder' => 'e.g. Convert trial signups'],
                ['label' => 'Key Features (3-5)', 'key' => 'key_features', 'type' => 'textarea', 'placeholder' => 'List your main value propositions'],
                ['label' => 'Tone & Style', 'key' => 'tone', 'type' => 'text', 'placeholder' => 'e.g. Professional, minimal, dark'],
                ['label' => 'Sections Needed', 'key' => 'sections', 'type' => 'textarea', 'placeholder' => 'e.g. Hero, Features, Pricing, FAQ, CTA'],
                ['label' => 'Inspiration / References', 'key' => 'references', 'type' => 'textarea', 'placeholder' => 'URLs or brief descriptions'],
            ],
        ]);
    }
}
