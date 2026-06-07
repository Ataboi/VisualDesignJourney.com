<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;

class StaticPageController extends Controller
{
    public function show(string $slug)
    {
        $pages = [
            'privacy' => ['Privacy Policy', 'Privacy and data handling information for Visual Design Journey.'],
            'terms' => ['Terms', 'Terms for using Visual Design Journey.'],
            'contact' => ['Contact', 'Contact Visual Design Journey for editorial, sponsor, and partnership questions.'],
            'sponsor' => ['Sponsor', 'Partnership and sponsor opportunities for Visual Design Journey.'],
            'premium' => ['Premium', 'Premium Visual Design Journey updates and tools.'],
            'editorial-policy' => ['Editorial Policy', 'Editorial standards for Visual Design Journey.'],
            'colors' => ['Colors', 'Color references and visual inspiration.'],
            'glossary' => ['Glossary', 'Design glossary and visual terminology.'],
            'archive' => ['Archive', 'Visual Design Journey archive.'],
            'vibe' => ['Vibe', 'Visual style exploration and mood references.'],
            'curators' => ['Curators', 'Curated visual design profiles.'],
            'trends' => ['Trends', 'Design trends and visual culture notes.'],
            'submit-source' => ['Submit Source', 'Submit a design source for review.'],
        ];

        abort_unless(isset($pages[$slug]), 404);

        return view('vdj.static.page', [
            'slug' => $slug,
            'title' => $pages[$slug][0],
            'description' => $pages[$slug][1],
        ]);
    }
}
