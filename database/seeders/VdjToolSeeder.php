<?php

namespace Database\Seeders;

use App\Models\BriefTemplate;
use App\Models\Category;
use App\Models\ColorPalette;
use App\Models\ColorReference;
use App\Models\CssSnippet;
use App\Models\DesignPrompt;
use App\Models\Tag;
use App\Models\TypeTool;
use App\Models\UiPattern;
use App\Models\WebsiteSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VdjToolSeeder extends Seeder
{
    public function run(): void
    {
        $toolCategory = $this->category('VDJ v2 Tools', 'vdj-v2-tools', 'tool', '#7F77DD');
        $colorCategory = $this->category('VDJ v2 Palettes', 'vdj-v2-palettes', 'color', '#C8C4D4');
        $promptCategory = $this->category('VDJ v2 Prompts', 'vdj-v2-prompts', 'prompt', '#574EB1');
        $sectionCategory = $this->category('VDJ v2 Sections', 'vdj-v2-sections', 'section', '#7F77DD');
        $briefCategory = $this->category('VDJ v2 Briefs', 'vdj-v2-briefs', 'brief', '#FFB020');

        foreach (['vdj-v2', 'editorial-ui', 'soft-purple', 'seo-safe', 'toolkit'] as $tag) {
            Tag::query()->firstOrCreate(
                ['slug' => $tag],
                ['name' => Str::headline($tag), 'color' => '#7F77DD', 'is_active' => true],
            );
        }

        $this->seedPalettes($colorCategory->id);
        $this->seedPrompts($promptCategory->id);
        $this->seedSections($sectionCategory->id);
        $this->seedBriefs($briefCategory->id);
        $this->seedCssSnippets();
        $this->seedUiPatterns();
        $this->seedTypeTools();
        $this->seedColorReferences();

        $toolCategory->forceFill(['description' => 'Seed data for VDJ v2 tool routes and empty-state protection.'])->save();
    }

    private function category(string $name, string $slug, string $type, string $color): Category
    {
        return Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'type' => $type,
                'color' => $color,
                'description' => 'Visual Design Journey v2 starter collection.',
                'is_active' => true,
            ],
        );
    }

    private function seedPalettes(int $categoryId): void
    {
        $palettes = [
            ['VDJ Editorial Light', 'vdj-acid-dark', ['#FAF8FF', '#FFFFFF', '#131B2E', '#454F5E', '#7F77DD'], 'editorial'],
            ['Gallery Neutral', 'gallery-neutral', ['#FFFFFF', '#F8F9FA', '#E9ECEF', '#454F5E', '#131B2E'], 'editorial'],
            ['Soft Purple System', 'editorial-contrast', ['#F2F3FF', '#C8C4D4', '#7F77DD', '#574EB1', '#131B2E'], 'accent'],
            ['Signal System', 'signal-system', ['#FFFFFF', '#6EE7B7', '#FFB020', '#FF4D4D', '#E9ECEF'], 'utility'],
        ];

        foreach ($palettes as [$name, $slug, $colors, $mood]) {
            ColorPalette::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'colors' => $colors,
                    'mood' => $mood,
                    'description' => 'VDJ v2 production palette for light editorial, gallery, and tool surfaces.',
                    'category_id' => $categoryId,
                    'is_featured' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedPrompts(int $categoryId): void
    {
        $prompts = [
            ['Redesign A Design Archive', 'redesign-design-archive', 'Turn an inspiration archive into a scan-friendly editorial interface with strong filters, canonical content blocks, and restrained purple accent color.', 'ui'],
            ['Build A Color Story', 'build-color-story', 'Create a five-color system with roles for page background, surface, text, accent, and signal states. Include usage notes.', 'branding'],
            ['Typography Mood Shift', 'typography-mood-shift', 'Take one layout and produce three typographic moods: editorial, utilitarian, and expressive. Keep the grid stable.', 'typography'],
            ['Component Critique Sprint', 'component-critique-sprint', 'Audit a UI component for hierarchy, spacing, contrast, empty states, and mobile behavior. Return concrete fixes.', 'ui'],
        ];

        foreach ($prompts as [$title, $slug, $prompt, $type]) {
            DesignPrompt::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'prompt' => $prompt,
                    'type' => $type,
                    'difficulty' => 'medium',
                    'tool' => 'Any',
                    'notes' => 'Seeded for VDJ v2 tool coverage.',
                    'category_id' => $categoryId,
                    'is_featured' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedSections(int $categoryId): void
    {
        $sections = [
            ['Tool Hub Hero', 'tool-hub-hero', 'hero', 'editorial', 'Dense light editorial tool hub intro with direct action links and no marketing fluff.'],
            ['Editorial Blog Index', 'editorial-blog-index', 'blog', 'editorial', 'Searchable blog index that preserves canonical and localized slug behavior.'],
            ['Gallery Browse Grid', 'gallery-browse-grid', 'gallery', 'visual', 'Responsive board grid with stable cards, avatars, likes, and save affordances.'],
            ['Admin Health Panel', 'admin-health-panel', 'dashboard', 'utility', 'Operational panel for structured data, SEO, media, and AdSense checks.'],
        ];

        foreach ($sections as [$title, $slug, $type, $style, $description]) {
            WebsiteSection::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'section_type' => $type,
                    'style' => $style,
                    'description' => $description,
                    'category_id' => $categoryId,
                    'is_featured' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedBriefs(int $categoryId): void
    {
        BriefTemplate::query()->updateOrCreate(
            ['slug' => 'vdj-v2-public-page-brief'],
            [
                'name' => 'VDJ v2 Public Page Brief',
                'industry' => 'Design / Publishing',
                'description' => 'A compact brief for public-facing VDJ pages, tools, and editorial updates.',
                'fields' => [
                    ['label' => 'Page Goal', 'key' => 'page_goal', 'type' => 'text'],
                    ['label' => 'Primary Audience', 'key' => 'audience', 'type' => 'text'],
                    ['label' => 'SEO Intent', 'key' => 'seo_intent', 'type' => 'textarea'],
                    ['label' => 'Visual Direction', 'key' => 'visual_direction', 'type' => 'textarea'],
                    ['label' => 'Must Keep URLs', 'key' => 'urls', 'type' => 'textarea'],
                ],
                'sample_output' => [
                    'tone' => 'clear, design-literate, search-friendly',
                    'checks' => ['canonical', 'hreflang', 'JSON-LD', 'mobile layout'],
                ],
                'category_id' => $categoryId,
                'is_featured' => true,
                'is_active' => true,
            ],
        );
    }

    private function seedCssSnippets(): void
    {
        foreach (CssSnippet::$typeLabels as $type => $label) {
            CssSnippet::query()->updateOrCreate(
                ['slug' => Str::slug('vdj-'.$label)],
                [
                    'name' => 'VDJ '.$label,
                    'snippet_type' => $type,
                    'description' => 'Production-ready editorial starter snippet for '.$label.'.',
                    'preview_css' => '.vdj-preview{background:#fff;color:#131B2E;border:.5px solid #E9ECEF;border-radius:16px;padding:24px;}',
                    'preview_html' => '<div class="vdj-preview">VDJ editorial snippet preview</div>',
                    'code' => "/* {$label} */\n.vdj-surface {\n  background: #ffffff;\n  color: #131B2E;\n  border: 0.5px solid #E9ECEF;\n  border-radius: 16px;\n  --vdj-accent: #7F77DD;\n}",
                    'language' => 'css',
                    'tags' => ['vdj-v2', 'editorial-css', $type],
                    'style' => 'editorial-light',
                    'is_active' => true,
                    'is_featured' => true,
                ],
            );
        }
    }

    private function seedUiPatterns(): void
    {
        foreach (UiPattern::$typeLabels as $type => $label) {
            UiPattern::query()->updateOrCreate(
                ['slug' => Str::slug('vdj-'.$label)],
                [
                    'name' => 'VDJ '.$label,
                    'pattern_type' => $type,
                    'description' => 'VDJ v2 light editorial starter pattern for '.$label.'.',
                    'preview_html' => '<section class="vdj-pattern"><strong>VDJ editorial</strong><span>'.$label.'</span></section>',
                    'code' => '<section class="rounded-lg border border-[#E9ECEF] bg-white p-6 text-[#131B2E]"><p class="text-xs font-semibold uppercase text-[#787583]">VDJ</p><strong class="mt-2 block text-lg font-semibold">'.$label.'</strong></section>',
                    'language' => 'html',
                    'style' => 'editorial-light',
                    'framework' => 'tailwind',
                    'tags' => ['vdj-v2', 'editorial-ui', $type],
                    'is_active' => true,
                    'is_featured' => true,
                ],
            );
        }
    }

    private function seedTypeTools(): void
    {
        foreach (TypeTool::$typeLabels as $type => $label) {
            TypeTool::query()->updateOrCreate(
                ['slug' => Str::slug('vdj-'.$label)],
                [
                    'name' => 'VDJ '.$label,
                    'tool_type' => $type,
                    'description' => 'Reference values for '.$label.' in the VDJ v2 interface.',
                    'values' => ['xs' => '12px', 'sm' => '14px', 'base' => '16px', 'lg' => '20px', 'xl' => '28px'],
                    'css_snippet' => ':root { --vdj-bg:#FAF8FF; --vdj-card:#FFFFFF; --vdj-border:#E9ECEF; --vdj-radius:16px; --vdj-accent:#7F77DD; }',
                    'tailwind_snippet' => "theme: { extend: { colors: { vdj: { bg: '#FAF8FF', card: '#FFFFFF', accent: '#7F77DD' } } } }",
                    'base_value' => '16px',
                    'ratio' => '1.250',
                    'use_case' => 'Use when extending VDJ v2 pages without drifting from the system.',
                    'is_active' => true,
                    'is_featured' => true,
                ],
            );
        }
    }

    private function seedColorReferences(): void
    {
        foreach (ColorReference::$typeLabels as $type => $label) {
            ColorReference::query()->updateOrCreate(
                ['slug' => Str::slug('vdj-'.$label)],
                [
                    'name' => 'VDJ '.$label,
                    'reference_type' => $type,
                    'description' => 'Color reference starter for '.$label.'.',
                    'colors' => ['#FAF8FF', '#FFFFFF', '#E9ECEF', '#131B2E', '#7F77DD'],
                    'css_code' => ':root { --bg:#FAF8FF; --surface:#FFFFFF; --border:#E9ECEF; --text:#131B2E; --accent:#7F77DD; }',
                    'tailwind_code' => "colors: { vdj: { bg: '#FAF8FF', surface: '#FFFFFF', border: '#E9ECEF', accent: '#7F77DD' } }",
                    'meta' => ['contrast_target' => 'WCAG AA', 'mode' => 'light'],
                    'style' => 'editorial-light',
                    'is_active' => true,
                    'is_featured' => true,
                ],
            );
        }
    }
}
