<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FontPairing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FontPairingSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Category::firstOrCreate(
            ['slug' => 'serif-sans'],
            ['name' => 'Serif & Sans', 'type' => 'font', 'is_active' => true]
        );

        $pairings = [
            // Editorial / Serif headers
            ['Playfair Display', '700', 'Inter',              '400', 'editorial', 'Design with intention.',       'Refined editorial for magazines and long-form content.'],
            ['Cormorant Garamond','600','Lato',               '300', 'elegant',   'Timeless elegance.',           'Delicate high-fashion pairing with a light body.'],
            ['Libre Baskerville', '700', 'Source Sans 3',     '400', 'editorial', 'Stories that endure.',         'Classic print-inspired pairing for content-heavy sites.'],
            ['Merriweather',      '700', 'Open Sans',         '400', 'editorial', 'Words that breathe.',          'Highly readable long-form combination.'],
            ['Lora',              '700', 'Nunito',            '400', 'editorial', 'Craft and clarity.',           'Warm serif heading with a friendly rounded body.'],
            ['EB Garamond',       '600', 'Raleway',           '400', 'elegant',   'Quiet luxury.',                'Old-style serif elevated by a geometric sans.'],
            ['Fraunces',          '700', 'Nunito',            '400', 'editorial', 'Warm and inviting.',           'Optical size serif paired with rounded humanist body.'],
            ['Spectral',          '700', 'Work Sans',         '400', 'editorial', 'Depth and dimension.',         'Nuanced serif with a neutral modern sans.'],
            ['Abril Fatface',     '400', 'Lato',              '300', 'bold',      'Make a statement.',            'Display poster heading with airy light body text.'],
            ['Rokkitt',           '700', 'Roboto',            '400', 'modern',    'Structured and clear.',        'Sturdy slab serif with the world\'s most used sans.'],
            ['Crimson Pro',       '600', 'DM Sans',           '400', 'elegant',   'Prose made beautiful.',        'Text-grade serif with a low-contrast humanist sans.'],
            ['Bitter',            '700', 'Hind',              '400', 'editorial', 'No-nonsense editorial.',       'Slab serif optimised for screens with a legible body.'],
            ['Unna',              '700', 'Mulish',            '300', 'elegant',   'Understated refinement.',      'Delicate italic-prone serif with a geometric body.'],
            ['Zilla Slab',        '700', 'Fira Sans',         '400', 'editorial', 'Open source clarity.',         'Mozilla\'s signature slab with its companion sans.'],

            // Bold / Display
            ['Syne',              '800', 'DM Sans',           '400', 'bold',      'Bold moves.',                  'Futuristic display weight paired with a minimal body.'],
            ['Bebas Neue',        '400', 'Open Sans',         '400', 'bold',      'COMMAND ATTENTION.',           'All-caps condensed display with a neutral body.'],
            ['Anton',             '400', 'Roboto',            '300', 'bold',      'GO LOUD.',                     'Impact-style display heading with a clean light body.'],
            ['Oswald',            '700', 'Source Sans 3',     '400', 'bold',      'Structure first.',             'Condensed grotesque heading with a versatile body.'],
            ['Barlow Condensed',  '700', 'Barlow',            '400', 'bold',      'Tight and powerful.',          'A condensed/regular pair from the same family.'],
            ['Righteous',         '400', 'Nunito Sans',       '400', 'bold',      'Retro future.',                'Groovy geometric with a clean neutral body.'],
            ['Black Han Sans',    '400', 'Noto Sans',         '400', 'bold',      'Heavy impact.',                'Ultra-bold Korean-influenced display with Noto body.'],
            ['Unbounded',         '700', 'Inter',             '400', 'bold',      'Zero compromise.',             'Geometric extended display with Inter body.'],
            ['Teko',              '600', 'Mukta',             '400', 'bold',      'Numbers that lead.',           'Condensed heading used widely in sports/data UIs.'],

            // Minimal / Modern
            ['Space Grotesk',     '700', 'Source Sans 3',     '400', 'modern',    'Built for the web.',           'Quirky geometric heading with a workhorse body.'],
            ['Plus Jakarta Sans', '700', 'Plus Jakarta Sans', '400', 'minimal',   'Unified system.',              'Single-family pairing for consistent modern UIs.'],
            ['Outfit',            '700', 'Outfit',            '400', 'minimal',   'Pure geometry.',               'Clean geometric family used across weights.'],
            ['Jost',              '700', 'Jost',              '400', 'minimal',   'Less is more.',                'Futura-inspired heading with its own regular weight.'],
            ['Manrope',           '700', 'Manrope',           '400', 'minimal',   'System native.',               'Humanist geometric working across all weights.'],
            ['Figtree',           '700', 'Figtree',           '400', 'minimal',   'Round and readable.',          'Friendly rounded geometric for product UIs.'],
            ['Albert Sans',       '700', 'Albert Sans',       '400', 'minimal',   'Quiet confidence.',            'Geometric sans with subtle humanist personality.'],
            ['Lexend',            '700', 'Lexend',            '400', 'minimal',   'Designed for reading.',        'Optimised for reading proficiency, any weight.'],
            ['Urbanist',          '700', 'Inter',             '400', 'minimal',   'Urban precision.',             'Geometric modernist heading with Inter body.'],
            ['Onest',             '700', 'Onest',             '400', 'minimal',   'Honest design.',               'Versatile new humanist sans across weights.'],

            // Humanist / Warm
            ['Raleway',           '700', 'Lato',              '400', 'modern',    'Elegant simplicity.',          'Art-deco inspired thin heading with a humanist body.'],
            ['Nunito',            '800', 'Nunito',            '400', 'playful',   'Soft and inviting.',           'Rounded geometric self-pairing for friendly UIs.'],
            ['Quicksand',         '700', 'Nunito Sans',       '400', 'playful',   'Approachable design.',         'Rounded display with a clear companion body.'],
            ['Comfortaa',         '700', 'Hind',              '400', 'playful',   'Rounded warmth.',              'Circular rounded display with a neutral body.'],
            ['Poppins',           '700', 'Poppins',           '400', 'modern',    'The modern classic.',          'Google\'s most popular geometric self-pair.'],
            ['Rubik',             '700', 'Rubik',             '400', 'modern',    'Softly geometric.',            'Slightly rounded grotesque self-pairing.'],
            ['Cabin',             '700', 'Cabin',             '400', 'modern',    'Humanist precision.',          'Humanist sans with strong letterforms.'],
            ['Mulish',            '700', 'Mulish',            '400', 'minimal',   'Low contrast, high clarity.',  'Optima-inspired geometric with even stroke weight.'],

            // Mono / Technical
            ['Space Mono',        '700', 'Inter',             '400', 'minimal',   'Code is design.',              'Fixed-width display with clean proportional body.'],
            ['Azeret Mono',       '700', 'DM Sans',           '400', 'minimal',   'Terminal aesthetic.',          'Monospaced heading for developer-focused products.'],

            // Mixed serif/sans classic combos
            ['Playfair Display',  '700', 'Raleway',           '400', 'editorial', 'Heritage with elegance.',      'Luxury editorial combining two distinctive voices.'],
            ['Josefin Sans',      '600', 'Josefin Slab',      '400', 'elegant',   'Matched geometry.',            'Sibling pairing: same skeleton, different texture.'],
            ['Cinzel',            '600', 'Fauna One',         '400', 'elegant',   'Classical authority.',         'Roman-inspired caps heading with a soft body.'],
            ['Yeseva One',        '400', 'Josefin Sans',      '300', 'editorial', 'Contrast at work.',            'Slab display with a geometric light-weight body.'],
            ['Archivo Black',     '400', 'Archivo',           '400', 'modern',    'One family, full range.',      'Bold black heading from the same family.'],
            ['Montserrat',        '700', 'Hind Siliguri',     '400', 'modern',    'Urban geometry.',              'Inspired by Buenos Aires street signs.'],
            ['Exo 2',             '700', 'Exo 2',             '400', 'modern',    'Sci-fi precision.',            'Slightly futuristic geometric self-pair.'],
            ['Kanit',             '700', 'Sarabun',           '400', 'modern',    'Southeast Asian influence.',   'Thai-Latin heading with a clean companion body.'],
        ];

        foreach ($pairings as [$hFont, $hWt, $bFont, $bWt, $style, $preview, $desc]) {
            $name = $hFont . ' + ' . $bFont;
            $slug = Str::slug($name);

            // Skip if already seeded
            if (FontPairing::where('slug', $slug)->exists()) {
                continue;
            }

            FontPairing::create([
                'name'           => $name,
                'slug'           => $slug,
                'heading_font'   => $hFont,
                'heading_weight' => $hWt,
                'heading_size'   => '2.5rem',
                'body_font'      => $bFont,
                'body_weight'    => $bWt,
                'body_size'      => '1rem',
                'style'          => $style,
                'preview_text'   => $preview,
                'description'    => $desc,
                'category_id'    => $cat->id,
                'is_active'      => true,
                'is_featured'    => false,
                'view_count'     => rand(10, 800),
            ]);
        }

        // Mark a varied set as featured
        $featuredNames = [
            'Playfair Display + Inter',
            'Syne + DM Sans',
            'Cormorant Garamond + Lato',
            'Space Grotesk + Source Sans 3',
            'Poppins + Poppins',
            'Bebas Neue + Open Sans',
            'Josefin Sans + Josefin Slab',
            'Plus Jakarta Sans + Plus Jakarta Sans',
        ];
        FontPairing::whereIn('name', $featuredNames)->update(['is_featured' => true]);
    }
}
