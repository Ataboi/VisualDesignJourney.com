<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\WebsiteSection;
use Illuminate\Http\Request;

class WebsiteSectionController extends Controller
{
    public function index(Request $request)
    {
        $query = WebsiteSection::where('is_active', true)->with(['category', 'tags']);

        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->section_type) {
            $query->where('section_type', $request->section_type);
        }

        if ($request->style) {
            $query->where('style', $request->style);
        }

        if ($request->tag) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }

        $sections = $query->latest()->paginate(12)->withQueryString();
        $tags = Tag::where('is_active', true)->get();
        $types = ['hero', 'navbar', 'features', 'pricing', 'testimonials', 'footer', 'cta', 'faq', 'gallery'];
        $styles = ['minimal', 'bold', 'editorial', 'glassmorphism', 'dark', 'light'];

        return view('website-sections.index', compact('sections', 'tags', 'types', 'styles'));
    }

    public function show(WebsiteSection $websiteSection)
    {
        $websiteSection->increment('view_count');
        $related = WebsiteSection::where('is_active', true)
            ->where('id', '!=', $websiteSection->id)
            ->where('section_type', $websiteSection->section_type)
            ->take(4)->get();
        return view('website-sections.show', compact('websiteSection', 'related'));
    }
}
